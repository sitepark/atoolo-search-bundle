<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

/**
 * @codeCoverageIgnore
 */
class ContentSectionTypeFacet extends FacetField
{
    /**
     * @param string[] $contentSectionTypes
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        array $contentSectionTypes,
        array $excludeFilter = []
    ) {
        parent::__construct(
            $key,
            $contentSectionTypes,
            $excludeFilter
        );
    }
}
