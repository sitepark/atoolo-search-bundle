<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

class FacetQuery extends Facet
{
    public function __construct(
        string $key,
        public readonly string $query,
        ?string $excludeFilter
    ) {
        parent::__construct($key, $excludeFilter);
    }
}
