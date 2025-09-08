<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

/**
 * @codeCoverageIgnore
 */
#[DiscriminatorMap(
    typeProperty: 'type',
    mapping: [
        'absoluteDateRange' => AbsoluteDateRangeFilter::class,
        'and' => AndFilter::class,
        'category' => CategoryFilter::class,
        'contentSectionType' => ContentSectionTypeFilter::class,
        'contentType' => ContentTypeFilter::class,
        'geoLocated' => GeoLocatedFilter::class,
        'group' => GroupFilter::class,
        'id' => IdFilter::class,
        'not' => NotFilter::class,
        'objectType' => ObjectTypeFilter::class,
        'or' => OrFilter::class,
        'query' => QueryFilter::class,
        'relativeDateRange' => RelativeDateRangeFilter::class,
        'site' => SiteFilter::class,
        'source' => SourceFilter::class,
        'spatialArbitraryRectangle' => SpatialArbitraryRectangleFilter::class,
        'spatialOrbital' => SpatialOrbitalFilter::class,
        'teaserProperty' => TeaserPropertyFilter::class,
    ],
)]
abstract class Filter
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public readonly ?string $key,
        public readonly array $tags = [],
    ) {}
}
