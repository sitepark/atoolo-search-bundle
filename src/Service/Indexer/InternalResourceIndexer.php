<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Dto\Indexer\IndexerParameter;
use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Indexer;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Solarium\QueryType\Update\Result as UpdateResult;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Throwable;

/**
 * Implementation of the indexer on the basis of a Solr index.
 *
 * Resources are loaded via the indexer, mapped to an IndexDocument and
 * then transferred to Solr in order to index it.
 *
 * This is done in several stages:
 *
 * 1. first, the PHP files containing the resource data are determined via
 *    the file system. This can be an entire directory tree or just
 *    individual files.
 * 2. resources may have been translated into several languages and are also
 *    available in translated form as PHP files in the file system. A separate
 *    Solr index is used for each language. The PHP files are therefore
 *    assigned to the respective language.
 * 3. the resources are loaded separately for each language, mapped to the
 *    index documents and indexed for the corresponding index.
 * 4. for performance reasons, the documents are not indexed individually,
 *    but always a list of documents. The entire list is divided into chunks
 *    and indexed chunk-wise.
 */
class InternalResourceIndexer implements Indexer
{
    private IndexerParameter $parameter;

    /**
     * @param iterable<DocumentEnricher<IndexDocument>> $documentEnricherList
     */
    public function __construct(
        private readonly iterable $documentEnricherList,
        private readonly ResourceFilter $resourceFilter,
        private IndexerProgressHandler $progressHandler,
        private readonly LocationFinder $finder,
        private readonly ResourceLoader $resourceLoader,
        private readonly TranslationSplitter $translationSplitter,
        private readonly SolrIndexService $indexService,
        private readonly IndexingAborter $aborter,
        private readonly IndexerConfigurationLoader $configLoader,
        private readonly string $source,
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly LockFactory $lockFactory = new LockFactory(
            new SemaphoreStore()
        ),
    ) {
    }

    public function enabled(): bool
    {
        return true;
    }

    public function getName(): string
    {
        return $this->getIndexerParameter()->name;
    }

    public function getProgressHandler(): IndexerProgressHandler
    {
        return $this->progressHandler;
    }

    public function setProgressHandler(
        IndexerProgressHandler $progressHandler
    ): void {
        $this->progressHandler = $progressHandler;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * @param string[] $idList
     */
    public function remove(array $idList): void
    {
        if (empty($idList)) {
            return;
        }

        $this->indexService->deleteByIdListForAllLanguages(
            $this->source,
            $idList
        );
        $this->indexService->commitForAllLanguages();
    }

    public function abort(): void
    {
        $this->aborter->requestAbortion($this->getBaseIndex());
    }

    /**
     * Indexes an entire directory structure or only selected files
     * if `paths` was specified in `$parameter`.
     */
    public function index(): IndexerStatus
    {
        $lock = $this->lockFactory->createLock(
            'indexer.' . $this->getBaseIndex()
        );
        if (!$lock->acquire()) {
            $this->logger->notice('Indexer is already running', [
                'index' => $this->getBaseIndex()
            ]);
            return $this->progressHandler->getStatus();
        }
        $param = $this->getIndexerParameter();

        $this->logger->info('Start indexing', [
            'index' => $this->getBaseIndex(),
            'chunkSize' => $param->chunkSize,
            'cleanupThreshold' => $param->cleanupThreshold,
        ]);

        $this->progressHandler->prepare('Collect resource locations');

        try {
            $paths = $this->finder->findAll();
            $this->deleteErrorProtocol($this->getBaseIndex());
            $total = count($paths);
            $this->progressHandler->start($total);

            $this->indexResources($param, $this->finder->findAll());
        } finally {
            $lock->release();
            $this->progressHandler->finish();
            gc_collect_cycles();
        }

        return $this->progressHandler->getStatus();
    }

    /**
     * @param string[] $paths
     */
    public function update(array $paths): IndexerStatus
    {
        $collectedPaths = $this->finder->findPaths($paths);
        $total = count($collectedPaths);
        $this->progressHandler->startUpdate($total);

        try {
            $param = $this->loadIndexerParameter();
            $this->indexResources($param, $collectedPaths);
        } finally {
            $this->progressHandler->finish();
            gc_collect_cycles();
        }

        return $this->progressHandler->getStatus();
    }

    private function getIndexerParameter(): IndexerParameter
    {
        return $this->parameter ??= ($this->loadIndexerParameter());
    }

    private function loadIndexerParameter(): IndexerParameter
    {
        $config = $this->configLoader->load($this->source);
        return new IndexerParameter(
            $config->name,
            $config->data->getInt(
                'cleanupThreshold'
            ),
            $config->data->getInt(
                'chunkSize',
                500
            )
        );
    }

    private function getBaseIndex(): string
    {
        return $this->indexService->getIndex('');
    }

    /**
     * Indexes the resources of all passed paths.
     *
     * @param array<string> $pathList
     */
    private function indexResources(
        IndexerParameter $parameter,
        array $pathList
    ): void {
        if (count($pathList) === 0) {
            return;
        }

        $splitterResult = $this->translationSplitter->split($pathList);
        $this->indexTranslationSplittedResources(
            $parameter,
            $splitterResult
        );
    }

    /**
     * There is a separate Solr index for each language. This allows
     * language-specific tokenizers and other language-relevant configurations
     * to be used. Via the `$splitterResult` all paths are separated according
     * to their languages and can be indexed separately. Each language is
     * indexed separately here.
     */
    private function indexTranslationSplittedResources(
        IndexerParameter $parameter,
        TranslationSplitterResult $splitterResult
    ): void {

        $processId = uniqid('', true);

        $index = $this->indexService->getIndex('');

        $this->indexResourcesPerLanguageIndex(
            $processId,
            $parameter,
            '',
            $index,
            $splitterResult->getBases()
        );

        foreach ($splitterResult->getLocales() as $locale) {
            $lang = substr($locale, 0, 2);
            $langIndex = $this->indexService->getIndex($lang);

            if ($index === $langIndex) {
                $this->handleError(
                    'No Index for language "' . $lang . '" ' .
                    'found (base index: "' . $index . '")'
                );
                continue;
            }

            $this->indexResourcesPerLanguageIndex(
                $processId,
                $parameter,
                $lang,
                $langIndex,
                $splitterResult->getTranslations($locale)
            );
        }
    }

    /**
     * The resources for a language are indexed here.
     *
     * @param string[] $pathList
     */
    private function indexResourcesPerLanguageIndex(
        string $processId,
        IndexerParameter $parameter,
        string $lang,
        string $index,
        array $pathList
    ): void {

        if (empty($pathList)) {
            return;
        }

        $offset = 0;
        $successCount = 0;

        $managedIndices = $this->indexService->getManagedIndices();
        if (!in_array($index, $managedIndices)) {
            $this->handleError('Index "' . $index . '" not found');
            return;
        }

        while (true) {
            $indexedCount = $this->indexChunks(
                $processId,
                $lang,
                $index,
                $pathList,
                $offset,
                $parameter->chunkSize
            );
            gc_collect_cycles();
            if ($indexedCount === false) {
                break;
            }
            $successCount += $indexedCount;
            $offset += $parameter->chunkSize;
        }

        if (
            $parameter->cleanupThreshold > 0 &&
            $successCount >= $parameter->cleanupThreshold
        ) {
            $this->indexService->deleteExcludingProcessId(
                $lang,
                $this->source,
                $processId
            );
        }
        $this->indexService->commit($lang);
    }

    /**
     * For performance reasons, not every resource is indexed individually,
     * but the index documents are first generated from several resources.
     * These are then passed to Solr for indexing via a request. These
     * methods accept a chunk with all paths that are to be indexed via a
     * request.
     *
     * @param string[] $pathList
     */
    private function indexChunks(
        string $processId,
        string $lang,
        string $index,
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
        if ($this->aborter->isAbortionRequested($index)) {
            $this->aborter->resetAbortionRequest($index);
            $this->progressHandler->abort();
            return false;
        }
        if (empty($resourceList)) {
            return 0;
        }
        $this->progressHandler->advance(count($resourceList));
        $result = $this->add($lang, $processId, $resourceList);

        if ($result->getStatus() !== 0) {
            $this->handleError($result->getResponse()->getStatusMessage());
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

        $maxLength = count($pathList) - $offset;
        if ($maxLength <= 0) {
            return false;
        }

        $end = min($length, $maxLength) + $offset;

        $resourceList = [];
        for ($i = $offset; $i < $end; $i++) {
            $path = $pathList[$i];
            try {
                $resource = $this->resourceLoader->load($path);
                $resourceList[] = $resource;
            } catch (Throwable $e) {
                $this->handleError($e);
            }
        }
        return $resourceList;
    }

    /**
     * @param array<Resource> $resources
     */
    private function add(
        string $lang,
        string $processId,
        array $resources
    ): UpdateResult {

        $updater = $this->indexService->updater($lang);

        foreach ($resources as $resource) {
            if ($this->resourceFilter->accept($resource) === false) {
                $this->progressHandler->skip(1);
                continue;
            }
            try {
                /** @var IndexSchema2xDocument $doc */
                $doc = $updater->createDocument();
                foreach ($this->documentEnricherList as $enricher) {
                    /** @var IndexSchema2xDocument $doc */
                    $doc = $enricher->enrichDocument(
                        $resource,
                        $doc,
                        $processId
                    );
                }
                $updater->addDocument($doc);
            } catch (Throwable $e) {
                $this->handleError($e);
            }
        }

        // this executes the query and returns the result
        return $updater->update();
    }

    private function handleError(Throwable|string $error): void
    {
        if (is_string($error)) {
            $error = new Exception($error);
        }
        $this->progressHandler->error($error);
        $this->logger->error(
            $error->getMessage(),
            [
                'exception' => $error,
            ]
        );
    }

    private function deleteErrorProtocol(string $core): void
    {
        $this->indexService->deleteByQuery(
            $core,
            'crawl_status:error OR crawl_status:warning'
        );
    }
}
