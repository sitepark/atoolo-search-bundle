<?php

declare(strict_types=1);

namespace Atoolo\Search\Test;

use Atoolo\Index\Dto\Indexer\IndexerStatus;
use Atoolo\Index\Indexer;
use Atoolo\Index\Service\Indexer\IndexerProgressHandler;

/**
 * Stands in for one of the indexers a host project registers itself.
 *
 * @codeCoverageIgnore
 */
class LegacyHostIndexer implements Indexer
{
    public function getName(): string
    {
        return 'Host Indexer';
    }

    public function getSource(): string
    {
        return 'host';
    }

    public function getProgressHandler(): IndexerProgressHandler
    {
        throw new \LogicException('not used in this test');
    }

    public function setProgressHandler(
        IndexerProgressHandler $progressHandler,
    ): void {}

    public function index(): IndexerStatus
    {
        return IndexerStatus::empty();
    }

    public function abort(): void {}

    public function enabled(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function remove(array $idList): void {}
}
