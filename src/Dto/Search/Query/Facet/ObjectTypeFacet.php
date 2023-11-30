<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

class ObjectTypeFacet extends FacetField
{
    /**
     * @param string[] $objectTypes
     */
    public function __construct(
        string $key,
        array $objectTypes,
        ?string $excludeFilter
    ) {
        parent::__construct(
            $key,
            'sp_objecttype',
            $objectTypes,
            $excludeFilter
        );
    }
}
