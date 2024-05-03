<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

/**
 * @codeCoverageIgnore
 */
class GroupFacet extends FacetField
{
    /**
     * @param string[] $groups
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        public readonly array $groups,
        array $excludeFilter = []
    ) {
        parent::__construct(
            $key,
            $groups,
            $excludeFilter
        );
    }
}
