<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceBaseLocator;
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
        private readonly ResourceBaseLocator $resourceBaseLocator,
        private readonly ResourceLoader $resourceLoader,
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
        $finder = new LocationFinder($this->resourceBaseLocator->locate());
        if (empty($parameter->paths)) {
            $pathList = $finder->findAll();
        } else {
            $pathList = $finder->findPaths($parameter->paths);
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

        $processId = uniqid('', true);
        $offset = 0;
        $successCount = 0;

        try {
            while (true) {
                $indexedCount = $this->indexChunks(
                    $processId,
                    $parameter->index,
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
                $this->deleteByProcessId($parameter->index, $processId);
            }
            $this->commit($parameter->index);
        } finally {
            $this->indexerProgressHandler->finish();
        }

        return $this->indexerProgressHandler->getStatus();
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
}
