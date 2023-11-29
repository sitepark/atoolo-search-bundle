<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

class FacetQuery implements Facet
{
    public function __construct(
        private readonly string $key,
        private readonly string $query
    ) {
    }
    public function getKey(): string
    {
        return $this->key;
    }
    public function getQuery(): string
    {
        return $this->query;
    }
}
