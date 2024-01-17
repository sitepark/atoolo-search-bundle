<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Dto\Indexer\IndexerParameter;
use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Indexer;
use Atoolo\Search\Service\SolrClientFactory;
use Exception;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\Update\Result;

/**
 *  Implementation of the indexer on the basis of a Solr index.
 */
class SolrIndexer implements Indexer
{
    /**
     * @param iterable<DocumentEnricher> $documentEnricherList
     */
    public function __construct(
        private readonly iterable $documentEnricherList,
        private readonly IndexerProgressHandler $indexerProgressHandler,
        private readonly LocationFinder $finder,
        private readonly ResourceLoader $resourceLoader,
        private readonly TranslationSplitter $translationSplitter,
        private readonly SolrClientFactory $clientFactory,
        private readonly IndexingAborter $aborter,
        private readonly string $source
    ) {
    }

    /**
     * @param string[] $idList
     */
    public function remove(string $index, array $idList): void
    {
        if (empty($idList)) {
            return;
        }

        $this->deleteByIdList($index, $idList);
        $this->commit($index);
    }

    public function abort($index): void
    {
        $this->aborter->abort($index);
    }

    public function index(IndexerParameter $parameter): IndexerStatus
    {
        if (empty($parameter->paths)) {
            $pathList = $this->finder->findAll();
        } else {
            $pathList = $this->finder->findPaths($parameter->paths);
        }

        return $this->indexResources($parameter, $pathList);
    }

    /**
     * @param array<string> $pathList
     */
    private function indexResources(
        IndexerParameter $parameter,
        array $pathList
    ): IndexerStatus {
        if (count($pathList) === 0) {
            return IndexerStatus::empty();
        }

        $total = count($pathList);
        if (empty($parameter->paths)) {
            $this->deleteErrorProtocol($parameter->index);
            $this->indexerProgressHandler->start($total);
        } else {
            $this->indexerProgressHandler->startUpdate($total);
        }


        $availableIndexes = $this->getAvailableIndexes();

        $processId = uniqid('', true);

        $splitterResult = $this->translationSplitter->split($pathList);

        if (in_array($parameter->index, $availableIndexes)) {
            $this->indexResourcesPerLanguageIndex(
                $processId,
                $parameter,
                $parameter->index,
                $splitterResult->getBases()
            );
        } else {
            $this->indexerProgressHandler->error(new Exception(
                'Index "' . $parameter->index . '" not found'
            ));
        }

        foreach ($splitterResult->getLocales() as $locale) {
            $localeIndex = $parameter->index . '-' . $locale;
            if (in_array($localeIndex, $availableIndexes)) {
                $this->indexResourcesPerLanguageIndex(
                    $processId,
                    $parameter,
                    $localeIndex,
                    $splitterResult->getTranslations($locale)
                );
            } else {
                $this->indexerProgressHandler->error(new Exception(
                    'Index "' . $localeIndex . '" not found'
                ));
            }
        }

        $this->indexerProgressHandler->finish();

        return $this->indexerProgressHandler->getStatus();
    }

    private function indexResourcesPerLanguageIndex(
        string $processId,
        IndexerParameter $parameter,
        string $index,
        array $pathList
    ): void {
        $offset = 0;
        $successCount = 0;

        while (true) {
            $indexedCount = $this->indexChunks(
                $processId,
                $index,
                $pathList,
                $offset,
                $parameter->chunkSize
            );
            if ($indexedCount === false) {
                break;
            }
            $successCount += $indexedCount;
            $offset += $parameter->chunkSize;
            gc_collect_cycles();
        }

        if (
            $parameter->cleanupThreshold > 0 &&
            $successCount >= $parameter->cleanupThreshold
        ) {
            $this->deleteByProcessId($index, $processId);
        }
        $this->commit($index);
    }

        /**
     * @param string[] $pathList
     */
    private function indexChunks(
        string $processId,
        string $solrCore,
        array $pathList,
        int $offset,
        int $length
    ): int|false {
        $resourceList = $this->loadResources(
            $pathList,
            $offset,
            $length
        );
        if ($resourceList === false) {
            return false;
        }
        if ($this->aborter->shouldAborted($solrCore)) {
            $this->aborter->aborted($solrCore);
            $this->indexerProgressHandler->abort();
            return false;
        }
        if (empty($resourceList)) {
            return 0;
        }
        $this->indexerProgressHandler->advance(count($resourceList));
        $result = $this->add($solrCore, $processId, $resourceList);

        if ($result->getStatus() !== 0) {
            $this->indexerProgressHandler->error(new Exception(
                $result->getResponse()->getStatusMessage()
            ));
            return 0;
        }

        return count($resourceList);
    }

    /**
     * @param string[] $pathList
     * @return Resource[]|false
     */
    private function loadResources(
        array $pathList,
        int $offset,
        int $length
    ): array|false {

        $maxLength = (count($pathList) ?? 0) - $offset;
        if ($maxLength <= 0) {
            return false;
        }

        if ($length > $maxLength) {
            $length = $maxLength;
        }

        $resourceList = [];
        for ($i = $offset; $i < ($length + $offset); $i++) {
            $path = $pathList[$i];
            try {
                $resource = $this->resourceLoader->load($path);
                $resourceList[] = $resource;
            } catch (InvalidResourceException $e) {
                $this->indexerProgressHandler->error($e);
            }
        }
        return $resourceList;
    }

    /**
     * @param string $solrCore
     * @param string $processId
     * @param array<Resource> $resources
     * @return ResultInterface|Result
     */
    private function add(
        string $solrCore,
        string $processId,
        array $resources
    ): ResultInterface|Result {
        $client = $this->clientFactory->create($solrCore);

        $update = $client->createUpdate();

        $documents = [];
        foreach ($resources as $resource) {
            foreach ($this->documentEnricherList as $enricher) {
                if (!$enricher->isIndexable($resource)) {
                    $this->indexerProgressHandler->skip(1);
                    continue 2;
                }
            }
            $doc = $update->createDocument();
            foreach ($this->documentEnricherList as $enricher) {
                $doc = $enricher->enrichDocument(
                    $resource,
                    $doc,
                    $processId
                );
            }
            $documents[] = $doc;
        }

        // add the documents and a commit command to the update query
        $update->addDocuments($documents);

        // this executes the query and returns the result
        return $client->update($update);
    }

    private function deleteByProcessId(string $core, string $processId): void
    {
        $this->deleteByQuery(
            $core,
            '-crawl_process_id:' . $processId . ' AND ' .
            ' sp_source:' . $this->source
        );
    }

    /**
     * @param string[] $idList
     */
    private function deleteByIdList(string $core, array $idList): void
    {
        $this->deleteByQuery(
            $core,
            'sp_id:(' . implode(' ', $idList) . ') AND ' .
            'sp_source:' . $this->source
        );
    }

    private function deleteErrorProtocol(string $core): void
    {
        $this->deleteByQuery(
            $core,
            'crawl_status:error OR crawl_status:warning'
        );
    }

    private function deleteByQuery(string $core, string $query): void
    {
        $client = $this->clientFactory->create($core);
        $update = $client->createUpdate();
        $update->addDeleteQuery($query);
        $client->update($update);
    }

    private function commit(string $core): void
    {
        $client = $this->clientFactory->create($core);
        $update = $client->createUpdate();
        $update->addCommit();
        $update->addOptimize();
        $client->update($update);
    }

    /**
     * @return string[]
     */
    private function getAvailableIndexes(): array
    {
        $client = $this->clientFactory->create('');
        $coreAdminQuery = $client->createCoreAdmin();
        $statusAction = $coreAdminQuery->createStatus();
        $coreAdminQuery->setAction($statusAction);

        $availableIndexes = [];
        $response = $client->coreAdmin($coreAdminQuery);
        foreach ($response->getStatusResults() as $statusResult) {
            $availableIndexes[] = $statusResult->getCoreName();
        }

        return $availableIndexes;
    }
}
