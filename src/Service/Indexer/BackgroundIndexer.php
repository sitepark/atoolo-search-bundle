<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Indexer;
use Atoolo\Search\Service\IndexName;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class BackgroundIndexer implements Indexer
{
    public function __construct(
        private readonly InternalResourceIndexer $indexer,
        private readonly IndexName $index,
        private readonly IndexerStatusStore $statusStore,
    ) {
    }

    public function enabled(): bool
    {
        return true;
    }
    public function getSource(): string
    {
        return $this->indexer->getSource();
    }

    public function getProgressHandler(): IndexerProgressHandler
    {
        return $this->indexer->getProgressHandler();
    }

    public function setProgressHandler(
        IndexerProgressHandler $progressHandler
    ): void {
        $this->indexer->setProgressHandler($progressHandler);
    }

    public function getName(): string
    {
        return "Background Indexer";
    }

    /**
     * @param string[] $idList
     */
    public function remove(array $idList): void
    {
        $this->indexer->remove($idList);
    }

    public function abort(): void
    {
        $this->indexer->abort();
    }

    public function index(): IndexerStatus
    {
         return $this->indexer->index();
    }

    /**
     * @param array<string> $paths
     */
    public function update(array $paths): IndexerStatus
    {
        return $this->indexer->update($paths);
    }

    /**
     * @throws ExceptionInterface
     */
    public function getStatus(): IndexerStatus
    {
        return $this->statusStore->load($this->getStatusStoreKey());
    }

    private function getStatusStoreKey(): string
    {
        return $this->index->name('') . '-' . $this->indexer->getSource();
    }
}
