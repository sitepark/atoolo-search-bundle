<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

/**
 * @codeCoverageIgnore
 */
class QueryFacet extends Facet
{
    /**
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        public readonly string $query,
        array $excludeFilter = []
    ) {
        parent::__construct($key, $excludeFilter);
    }
}
