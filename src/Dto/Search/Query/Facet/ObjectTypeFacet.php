<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

/**
 * @codeCoverageIgnore
 */
class ObjectTypeFacet extends FacetField
{
    /**
     * @param string[] $objectTypes
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        public readonly array $objectTypes,
        array $excludeFilter = []
    ) {
        parent::__construct(
            $key,
            $objectTypes,
            $excludeFilter
        );
    }
}
