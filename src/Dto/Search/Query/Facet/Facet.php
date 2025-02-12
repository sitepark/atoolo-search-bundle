<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

/**
 * @codeCoverageIgnore
 */
#[DiscriminatorMap(
    typeProperty: 'type',
    mapping: [
        'absoluteDateRange' => AbsoluteDateRangeFacet::class,
        'category' => CategoryFacet::class,
        'contentSectionType' => ContentSectionTypeFacet::class,
        'contentType' => ContentTypeFacet::class,
        'group' => GroupFacet::class,
        'multiQuery' => MultiQueryFacet::class,
        'objectType' => ObjectTypeFacet::class,
        'query' => QueryFacet::class,
        'relativeDateRange' => RelativeDateRangeFacet::class,
        'site' => SiteFacet::class,
        'source' => SourceFacet::class,
        'spatialDistanceRange' => SpatialDistanceRangeFacet::class,
    ],
)]
abstract class Facet
{
    /**
     * @param string[] $excludeFilter
     */
    public function __construct(
        public readonly string $key,
        public readonly array $excludeFilter = [],
    ) {}
}
