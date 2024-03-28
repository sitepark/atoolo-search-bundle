<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

use Atoolo\Search\Dto\Indexer\IndexerConfiguration;
use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Indexer;
use Atoolo\Search\Service\Indexer\IndexerConfigurationLoader;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use Atoolo\Search\Service\Indexer\IndexingAborter;

abstract class AbstractIndexer implements Indexer
{
    private IndexerConfiguration $config;

    public function __construct(
        protected readonly IndexName $indexName,
        protected IndexerProgressHandler $progressHandler,
        protected readonly IndexingAborter $aborter,
        protected readonly IndexerConfigurationLoader $configLoader,
        protected readonly string $source,
    ) {
    }

    protected function getKey(): string
    {
        return $this->indexName->name('') . '-' . $this->source;
    }

    protected function getConfig(): IndexerConfiguration
    {
        return $this->config ??= $this->configLoader->load($this->source);
    }

    public function getName(): string
    {
        return $this->getConfig()->name;
    }

    public function getSource(): string
    {
        return $this->source;
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

    public function abort(): void
    {
        $this->aborter->requestAbortion($this->getKey());
    }

    protected function isAbortionRequested(): bool
    {
        return $this->aborter->isAbortionRequested($this->getKey());
    }

    public function enabled(): bool
    {
        return $this->configLoader->exists($this->source);
    }

    /**
     * @inheritDoc
     */
    abstract public function index(): IndexerStatus;

    /**
     * @inheritDoc
     */
    abstract public function remove(array $idList): void;
}
