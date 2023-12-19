<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Result;

use ArrayIterator;
use Atoolo\Resource\Resource;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<Resource>
 */
class SearchResult implements IteratorAggregate
{
    /**
     * @param Resource[] $results
     * @param FacetGroup[] $facetGroups
     */
    public function __construct(
        private readonly int $total,
        private readonly int $limit,
        private readonly int $offset,
        private readonly array $results,
        private readonly array $facetGroups,
        private readonly int $queryTime
    ) {
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->results);
    }

    /**
     * @return Resource[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return FacetGroup[]
     */
    public function getFacetGroups(): array
    {
        return $this->facetGroups;
    }

    public function getQueryTime(): int
    {
        return $this->queryTime;
    }
}
