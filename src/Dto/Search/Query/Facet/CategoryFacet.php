<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

/**
 * @codeCoverageIgnore
 */
class CategoryFacet extends FacetField
{
    /**
     * @param string[] $categories
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        array $categories,
        array $excludeFilter = []
    ) {
        parent::__construct(
            $key,
            $categories,
            $excludeFilter
        );
    }
}
