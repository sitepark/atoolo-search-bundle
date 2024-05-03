<?php

declare(strict_types=1);

namespace Atoolo\Search;

use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;

/**
 * The service interface for indexing a search index.
 *
 * The main task of an indexer is to systematically analyze documents or
 * content in order to extract relevant information from them. This information
 * is structured and stored in a search index to enable efficient search
 * queries. The indexer organizes the data and extracts hierarchical structures
 * that search engines use to deliver fast and accurate search results.
 */
interface Indexer
{
    public function getName(): string;

    public function getSource(): string;

    public function getProgressHandler(): IndexerProgressHandler;

    public function setProgressHandler(
        IndexerProgressHandler $progressHandler
    ): void;

    public function index(): IndexerStatus;

    public function abort(): void;

    public function enabled(): bool;

    /**
     * @param string[] $idList
     */
    public function remove(array $idList): void;
}
