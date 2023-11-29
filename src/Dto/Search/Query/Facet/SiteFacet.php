<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

class SiteFacet extends FacetField
{
    public function __construct(string $key, string ...$site)
    {
        parent::__construct($key, 'sp_site', $site);
    }
}
