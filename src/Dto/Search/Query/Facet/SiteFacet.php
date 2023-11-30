<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

class SiteFacet extends FacetField
{
    /**
     * @param string[] $sites
     */
    public function __construct(
        string $key,
        array $sites,
        ?string $excludeFilter
    ) {
        parent::__construct(
            $key,
            'sp_site',
            $sites,
            $excludeFilter
        );
    }
}
