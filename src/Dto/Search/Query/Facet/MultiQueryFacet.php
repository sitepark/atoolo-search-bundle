<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

/**
 * @codeCoverageIgnore
 */
class MultiQueryFacet extends Facet
{
    /**
     * @param QueryFacet[] $queries
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        public readonly array $queries,
        array $excludeFilter = [],
    ) {
        parent::__construct($key, $excludeFilter);
    }
}
