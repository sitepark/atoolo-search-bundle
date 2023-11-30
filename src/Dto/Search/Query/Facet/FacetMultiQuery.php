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
        private readonly array $queryList,
        private readonly ?string $excludeFilter
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

    public function getExcludeFilter(): ?string
    {
        return $this->excludeFilter;
    }
}
