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
class ResourceSearchResult implements IteratorAggregate
{
    /**
     * @param Resource[] $resourceList
     * @param FacetGroup[] $facetGroupList
     */
    public function __construct(
        private readonly int $total,
        private readonly int $offset,
        private readonly array $resourceList,
        private readonly array $facetGroupList,
        private readonly int $queryTime
    ) {
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->resourceList);
    }

    /**
     * @return Resource[]
     */
    public function getResourceList(): array
    {
        return $this->resourceList;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @return FacetGroup[]
     */
    public function getFacetGroupList(): array
    {
        return $this->facetGroupList;
    }

    public function getQueryTime(): int
    {
        return $this->queryTime;
    }
}
