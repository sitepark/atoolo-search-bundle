<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\ResourceBaseLocator;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Dto\Indexer\IndexerParameter;
use Atoolo\Search\Indexer;
use Atoolo\Search\Service\SolrClientFactory;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

class BackgroundIndexer implements Indexer
{
    private LockFactory $lockFactory;

    /**
     * @param iterable<DocumentEnricher> $documentEnricherList
     */
    public function __construct(
        private readonly iterable $documentEnricherList,
        private readonly ResourceBaseLocator $resourceBaseLocator,
        private readonly ResourceLoader $resourceLoader,
        private readonly SolrClientFactory $clientFactory,
        private readonly string $source,
        private readonly string $statusCacheDir
    ) {
        $this->lockFactory = new LockFactory(new SemaphoreStore());
        if (
            !is_dir($concurrentDirectory = $this->statusCacheDir) &&
            !mkdir($concurrentDirectory) &&
            !is_dir($concurrentDirectory)
        ) {
            throw new \RuntimeException(sprintf(
                'Directory "%s" was not created',
                $concurrentDirectory
            ));
        }
    }

    public function index(IndexerParameter $parameter): string
    {
        $lock = $this->lockFactory->createLock($parameter->index);
        if (!$lock->acquire()) {
            return '';
        }
        try {
            return $this->getIndexer($parameter->index)->index($parameter);
        } finally {
            $lock->release();
        }
    }

    public function getStatus(string $index): ?BackgroundIndexerStatus
    {
        $file = $this->getStatusFile($index);
        return BackgroundIndexerStatus::load($file);
    }

    private function getIndexer(string $index): SolrIndexer
    {
        $progressHandler = new BackgroundIndexerProgressState(
            $this->getStatusFile($index)
        );
        return new SolrIndexer(
            $this->documentEnricherList,
            $progressHandler,
            $this->resourceBaseLocator,
            $this->resourceLoader,
            $this->clientFactory,
            $this->source,
        );
    }

    private function getStatusFile(string $index): string
    {
        return $this->statusCacheDir .
            '/atoolo.search.index.' . $index . ".status.json";
    }
}
