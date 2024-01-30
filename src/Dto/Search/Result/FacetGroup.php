<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Result;

class FacetGroup
{
    /**
     * @param Facet[] $facets
     */
    public function __construct(
        private readonly string $key,
        private readonly array $facets
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return Facet[]
     */
    public function getFacets(): array
    {
        return $this->facets;
    }
}
