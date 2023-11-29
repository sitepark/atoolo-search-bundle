<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

class ObjectTypeFacet extends FacetField
{
    public function __construct(string $key, string ...$terms)
    {
        parent::__construct($key, 'sp_objecttype', $terms);
    }
}
