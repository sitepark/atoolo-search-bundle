<?php

declare(strict_types=1);

namespace Atoolo\Search;

use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Result\SearchResult;

/**
 * The service interface for a search with full-text, filter and facet support.
 */
interface Search
{
    public function search(SearchQuery $query): SearchResult;
}
