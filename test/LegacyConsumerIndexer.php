<?php

declare(strict_types=1);

namespace Atoolo\Search\Test;

use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Service\AbstractIndexer;
use Atoolo\Search\Service\IndexName;
use Atoolo\Search\Service\Indexer\IndexerConfigurationLoader;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use Atoolo\Search\Service\Indexer\IndexingAborter;

/**
 * Written exactly like the indexers of the consumer projects: it extends the
 * deprecated `AbstractIndexer` and type hints the deprecated names in its
 * constructor, while the container hands over objects of the new classes.
 *
 * @codeCoverageIgnore
 */
class LegacyConsumerIndexer extends AbstractIndexer
{
    public function __construct(
        IndexName $indexName,
        IndexerProgressHandler $progressHandler,
        IndexingAborter $aborter,
        IndexerConfigurationLoader $configLoader,
        string $source,
    ) {
        parent::__construct(
            $indexName,
            $progressHandler,
            $aborter,
            $configLoader,
            $source,
        );
    }

    public function index(): IndexerStatus
    {
        return IndexerStatus::empty();
    }

    /**
     * @inheritDoc
     */
    public function remove(array $idList): void {}
}
