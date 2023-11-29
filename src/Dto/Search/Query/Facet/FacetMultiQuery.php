<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

class FacetMultiQuery implements Facet
{
    /**
     * @param string $key
     * @param FacetQuery[] $queryList
     */
    public function __construct(
        private readonly string $key,
        private readonly array $queryList
    ) {
    }
    public function getKey(): string
    {
        return $this->key;
    }
    /**
     * @return FacetQuery[]
     */
    public function getQueryList(): array
    {
        return $this->queryList;
    }
}
