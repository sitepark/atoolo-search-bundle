<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\Sort\Criteria;

/**
 * @codeCoverageIgnore
 */
class SearchQuery
{
    /**
     * @param Criteria[] $sort
     * @param Filter[] $filter
     * @param Facet[] $facets
     * @internal Do not use the constructor directly,
     *  but the SearchQueryBuilder
     */
    public function __construct(
        public readonly string $text,
        public readonly string $lang,
        public readonly int $offset,
        public readonly int $limit,
        public readonly array $sort,
        public readonly array $filter,
        public readonly array $facets,
        public readonly QueryOperator $defaultQueryOperator
    ) {
    }
}
