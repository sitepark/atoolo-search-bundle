<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Result;

class FacetGroup
{
    /**
     * @param Facet[] $facetList
     */
    public function __construct(
        private readonly string $key,
        private readonly array $facetList
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return Facet[]
     */
    public function getFacetList(): array
    {
        return $this->facetList;
    }
}
