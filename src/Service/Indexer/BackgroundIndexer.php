<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Search\Dto\Indexer\IndexerParameter;
use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Indexer;
use Atoolo\Search\Service\IndexName;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class BackgroundIndexer implements Indexer
{
    public function __construct(
        private readonly InternalResourceIndexerFactory $indexerFactory,
        private readonly IndexName $index,
        private readonly IndexerStatusStore $statusStore,
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly LockFactory $lockFactory = new LockFactory(
            new SemaphoreStore()
        )
    ) {
    }

    /**
     * @param string[] $idList
     */
    public function remove(array $idList): void
    {
        $this->getIndexer()->remove($idList);
    }

    public function abort(): void
    {
        $this->getIndexer()->abort();
    }

    public function index(IndexerParameter $parameter): IndexerStatus
    {
        $lock = $this->lockFactory->createLock($this->getIndex());
        if (!$lock->acquire()) {
            return IndexerStatus::empty();
        }
        try {
            return $this->getIndexer()->index($parameter);
        } finally {
            $lock->release();
        }
    }

    /**
     * @throws ExceptionInterface
     */
    public function getStatus(): IndexerStatus
    {
        return $this->statusStore->load($this->getIndex());
    }

    private function getIndexer(): InternalResourceIndexer
    {
        $progressHandler = new BackgroundIndexerProgressState(
            $this->index->name(''),
            $this->statusStore,
            $this->logger
        );

        return $this->indexerFactory->create($progressHandler);
    }

    private function getIndex(): string
    {
        /*
         * The indexer always requires the default index, as the language is
         * determined via the resources to be indexed.
         */
        return $this->index->name('');
    }
}
