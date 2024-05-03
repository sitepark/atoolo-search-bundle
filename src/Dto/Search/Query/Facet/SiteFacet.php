<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

/**
 * @codeCoverageIgnore
 */
class SiteFacet extends FacetField
{
    /**
     * @param string[] $sites
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        public readonly array $sites,
        array $excludeFilter = []
    ) {
        parent::__construct(
            $key,
            $sites,
            $excludeFilter
        );
    }
}
