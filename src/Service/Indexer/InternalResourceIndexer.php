<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Search\Dto\Indexer\IndexerParameter;
use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Exception\UnsupportedIndexLanguageException;
use Atoolo\Search\Indexer;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Solarium\QueryType\Update\Result as UpdateResult;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
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

    private bool $skipCleanup = false;

    /**
     * @var array<string>|null
     */
    private ?array $managedIndices = null;

    /**
     * @var array<string, string|false>
     */
    private array $validIndexnameByLanguage = [];

    /**
     * @param iterable<DocumentEnricher<IndexDocument>> $documentEnricherList
     */
    public function __construct(
        private readonly iterable $documentEnricherList,
        private readonly ResourceFilter $resourceFilter,
        private IndexerProgressHandler $progressHandler,
        private readonly LocationFinder $finder,
        private readonly ResourceLoader $resourceLoader,
        private readonly SolrIndexService $indexService,
        private readonly IndexingAborter $aborter,
        private readonly IndexerConfigurationLoader $configLoader,
        private readonly string $source,
        private readonly ?PhpLimitIncreaser $limitIncreaser,
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly LockFactory $lockFactory = new LockFactory(
            new SemaphoreStore(),
        ),
    ) {}

    public function enabled(): bool
    {
        return true;
    }

    /**
     * @throws ExceptionInterface
     */
    public function getStatus(): IndexerStatus
    {
        return $this->progressHandler->getStatus();
    }

    public function getIndex(ResourceLanguage $lang): string
    {
        return $this->indexService->getIndex($lang);
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
        IndexerProgressHandler $progressHandler,
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
            $idList,
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
            'indexer.' . $this->getBaseIndex(),
        );
        if (!$lock->acquire()) {
            $this->logger->notice('Indexer is already running', [
                'index' => $this->getBaseIndex(),
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
            $this->limitIncreaser?->increase();
            $paths = $this->finder->findAll($param->excludes);
            $this->deleteErrorProtocol();
            $total = count($paths);
            $this->progressHandler->start($total);

            $this->indexResources($param, $paths);
        } finally {
            // should already be cleaned up by the gc
            unset($paths);
            gc_collect_cycles();
            $this->limitIncreaser?->reset();
            $lock->release();
            $this->progressHandler->finish();
        }

        return $this->progressHandler->getStatus();
    }

    /**
     * @param string[] $paths
     */
    public function update(array $paths): IndexerStatus
    {

        $this->skipCleanup = true;

        $param = $this->loadIndexerParameter();

        $this->limitIncreaser?->increase();
        try {
            $collectedPaths = $this->finder->findPaths(
                $paths,
                $param->excludes,
            );

            $total = count($collectedPaths);
            $this->progressHandler->startUpdate($total);

            $this->indexResources($param, $collectedPaths);
        } finally {
            // should already be cleaned up by the gc
            unset($collectedPaths);
            gc_collect_cycles();

            $this->limitIncreaser?->reset();
            $this->progressHandler->finish();
        }

        return $this->progressHandler->getStatus();
    }

    private function getIndexerParameter(): IndexerParameter
    {
        return $this->parameter ??= ($this->loadIndexerParameter());
    }

    /**
     * @return array<string>
     */
    public function getManagedIndices(): array
    {
        return $this->managedIndices ??= $this->indexService->getManagedIndices();
    }

    private function loadIndexerParameter(): IndexerParameter
    {
        $config = $this->configLoader->load($this->source);
        /** @var string[] $excludes */
        $excludes = $config->data->getArray(
            'excludes',
        );
        return new IndexerParameter(
            $config->name,
            $config->data->getInt(
                'cleanupThreshold',
                1000,
            ),
            $config->data->getInt(
                'chunkSize',
                500,
            ),
            $excludes,
        );
    }

    private function getBaseIndex(): string
    {
        return $this->indexService->getIndex(ResourceLanguage::default());
    }

    /**
     * Indexes the resources of all passed paths.
     *
     * @param array<string> $pathList
     */
    private function indexResources(
        IndexerParameter $parameter,
        array $pathList,
    ): void {
        if (count($pathList) === 0) {
            return;
        }
        $offset = 0;
        $successCount = 0;
        $updatedIndexLocales = [];
        $processId = uniqid('', true);
        $sortedPathlist = $this->sortPathlistByLocale($pathList);

        while (true) {
            $resourceLists = $this->loadResources(
                $sortedPathlist,
                $offset,
                $parameter->chunkSize,
            );
            if ($resourceLists === false) {
                break;
            }

            foreach ($resourceLists as $resourceLang => $resourceList) {
                $lang = ResourceLanguage::of($resourceLang);
                $indexedCount = $this->indexResourcesForLanguage(
                    $lang,
                    $resourceList,
                    $processId,
                );
                if ($indexedCount === false) {
                    continue;
                }
                $successCount += $indexedCount;

                if (!in_array($lang, $updatedIndexLocales)) {
                    $updatedIndexLocales[] = $lang;
                }
            }
            $offset += $parameter->chunkSize;
        }

        $cleanupThreshold = $parameter->cleanupThreshold;
        if ($cleanupThreshold > 0 && $successCount >= $cleanupThreshold) {
            $this->commitLocaleIndices($updatedIndexLocales, $processId);
        }
    }

    /**
     * @param ResourceLanguage $resourceLang
     * @param array<Resource> $resourceList
     * @param string $processId
     * @return int|false
     */
    private function indexResourcesForLanguage(
        ResourceLanguage $resourceLang,
        array $resourceList,
        string $processId,
    ): int|false {

        $index = $this->getIndexByLanguage($resourceLang);
        if ($index === false) {
            return false;
        }
        $indexedCount = $this->indexChunks(
            $processId,
            $resourceLang,
            $index,
            $resourceList,
        );
        gc_collect_cycles();
        return  $indexedCount;
    }

    /**
     * @param array<ResourceLanguage> $indexLocales
     * @param string $processId
     * @return void
     */
    private function commitLocaleIndices(
        array $indexLocales,
        string $processId,
    ): void {

        if (!$this->skipCleanup) {
            foreach ($indexLocales as $indexLocale) {
                $this->indexService->deleteExcludingProcessId(
                    $indexLocale,
                    $this->source,
                    $processId,
                );
                $this->indexService->commit($indexLocale);
            }
        }
    }

    /**
     * For performance reasons, not every resource is indexed individually,
     * but the index documents are first generated from several resources.
     * These are then passed to Solr for indexing via a request. These
     * methods accept a chunk with all paths that are to be indexed via a
     * request.
     *
     * @param string $processId
     * @param ResourceLanguage $lang
     * @param string $index
     * @param array<Resource> $resourceList
     */
    private function indexChunks(
        string $processId,
        ResourceLanguage $lang,
        string $index,
        array $resourceList,
    ): int|false {

        if ($this->aborter->isAbortionRequested($index)) {
            $this->aborter->resetAbortionRequest($index);
            $this->progressHandler->abort();
            return false;
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
     * Load the <Resource> objects for all $locations and return them in a
     * associative array with all resources for each found locale
     * within all the resources.
     *
     * @param string[] $locations
     * @poram int $offset
     * @poram int $length
     * @return  array<string, array<Resource>>|false
     */
    private function loadResources(
        array $locations,
        int $offset,
        int $length,
    ): array|false {

        $maxLength = count($locations) - $offset;
        if ($maxLength <= 0) {
            return false;
        }

        $end = min($length, $maxLength) + $offset;

        $resourceLists = [];
        for ($i = $offset; $i < $end; $i++) {
            $resourceLocation = $this->toResourceLocation($locations[$i]);
            if ($resourceLocation === null) {
                continue;
            }

            try {
                $resource = $this->resourceLoader->load($resourceLocation);
                if (array_key_exists($resource->lang->code, $resourceLists) === false) {
                    $resourceLists[$resource->lang->code] = [];
                }
                $resourceLists[$resource->lang->code][] = $resource;
            } catch (Throwable $e) {
                $this->handleError($e);
            }
        }
        return $resourceLists;
    }

    /**
     * @param array<Resource> $resources
     */
    private function add(
        ResourceLanguage $lang,
        string $processId,
        array $resources,
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
                        $processId,
                    );
                }
                foreach ($this->documentEnricherList as $enricher) {
                    $enricher->cleanup();
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
            ],
        );
    }

    private function deleteErrorProtocol(): void
    {
        $this->indexService->deleteByQuery(
            ResourceLanguage::default(),
            'crawl_status:error OR crawl_status:warning',
        );
    }

    /**
     * Sorts the pathList by the translated locals within the path:
     * `/dir/file.php` is lower than
     * `/dir/anotherFile.php.translations/de_DE.php` is lower than
     * `/dir/file.php.translations/it_IT.php` is equal to
     * `/dir/nextFile.php.translations/it_IT.php`
     *
     * @param array<string> $pathList
     * @return array<string> $sortedList
     */
    private function sortPathlistByLocale(array $pathList): array
    {
        usort($pathList, function (string $pathA, string $pathB) {
            $posA = strrpos($pathA, '.php.translations');
            $posB = strrpos($pathB, '.php.translations');
            if ($posA === false || $posB === false) {
                $eq = $posA <=> $posB;
                return $eq;
            }
            $localeA = substr($pathA, strrpos($pathA, '/') + 1);
            $localeB = substr($pathB, strrpos($pathB, '/') + 1);
            return $localeA <=> $localeB;
        });
        return $pathList;
    }

    /**
     * Converts a path like '/dir/file_a.php' or
     * '/dir/file_b.php.translations/de_DE.php' to corresponding
     * ResourceLocation object with the location and ResourceLanguage of th path
     *
     * @param string $path
     * @return ResourceLocation|null
     */
    private function toResourceLocation(string $path): ?ResourceLocation
    {
        $normalizedPath = $this->normalizePath($path);
        if (empty($normalizedPath)) {
            return null;
        }

        $pos = strrpos($normalizedPath, '.php.translations');
        if ($pos === false) {
            return ResourceLocation::of($normalizedPath);
        }

        $localeFilename = basename($normalizedPath);
        $locale = basename($localeFilename, '.php');

        return ResourceLocation::of(
            substr($normalizedPath, 0, $pos + 4),
            ResourceLanguage::of($locale),
        );
    }

    /**
     * A path can signal to be translated into another language via
     * the URL parameter loc. For example,
     * `/dir/file.php?loc=it_IT` defines that the path
     * `/dir/file.php.translations/it_IT.php` is to be used.
     * This method translates the URL parameter into the correct path.
     */
    private function normalizePath(string $path): string
    {
        $queryString = parse_url($path, PHP_URL_QUERY);
        if (!is_string($queryString)) {
            return $path;
        }
        $urlPath = parse_url($path, PHP_URL_PATH);
        if (!is_string($urlPath)) {
            return '';
        }
        parse_str($queryString, $params);
        if (!isset($params['loc']) || !is_string($params['loc'])) {
            return $urlPath;
        }
        $loc = $params['loc'];
        return $urlPath . '.translations/' . $loc . ".php";
    }

    /**
     * @param ResourceLanguage $lang
     * @return false|string
     */
    private function getIndexByLanguage(ResourceLanguage $lang): false|string
    {
        $index = $this->validIndexnameByLanguage[$lang->code] ?? null;
        if ($index === null) {
            try {
                $index = $this->indexService->getIndex($lang);
                if (!in_array($index, $this->getManagedIndices())) {
                    $this->handleError('Unmanaged Index: ' . $index);
                    $index = false;
                }
            } catch (UnsupportedIndexLanguageException $e) {
                $this->handleError($e->getMessage());
                $index = false;
            }
            $this->validIndexnameByLanguage[$lang->code] = $index;
        }
        return $index;
    }
}
