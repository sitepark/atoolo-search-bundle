<?php

declare(strict_types=1);

namespace Atoolo\Search;

use Atoolo\Search\Dto\Indexer\IndexerParameter;

/**
 * The service interface for indexing a search index.
 *
 * The main task of an indexer is to systematically analyze documents or
 * content in order to extract relevant information from them. This information
 * is structured and stored in a search index to enable efficient search
 * queries. The indexer organizes the data and extract hierarchical structure
 * that search engines use to deliver fast and accurate search results.
 */
interface Indexer
{
    /**
     * @return string process id
     */
    public function index(IndexerParameter $parameter): string;
}
