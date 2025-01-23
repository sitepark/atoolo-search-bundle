<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\Facet\AbsoluteDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\CategoryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ContentSectionTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ContentTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Facet\GroupFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ObjectTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\RelativeDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\SiteFacet;
use Atoolo\Search\Dto\Search\Query\Facet\SourceFacet;
use Atoolo\Search\Dto\Search\Query\Filter\AbsoluteDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\CategoryFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ContentSectionTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ContentTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\Filter\GeoLocatedFilter;
use Atoolo\Search\Dto\Search\Query\Filter\GroupFilter;
use Atoolo\Search\Dto\Search\Query\Filter\IdFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ObjectTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\RelativeDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SiteFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SourceFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialArbitraryRectangleFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialOrbitalFilter;
use Atoolo\Search\Dto\Search\Query\Sort\Criteria;
use Atoolo\Search\Dto\Search\Query\Sort\CustomField;
use Atoolo\Search\Dto\Search\Query\Sort\Date;
use Atoolo\Search\Dto\Search\Query\Sort\Name;
use Atoolo\Search\Dto\Search\Query\Sort\Natural;
use Atoolo\Search\Dto\Search\Query\Sort\Score;
use Atoolo\Search\Dto\Search\Query\Sort\SpatialDist;
use InvalidArgumentException;

class Schema2xFieldMapper
{
    public function getFacetField(Facet $facet): string
    {
        return match (true) {
            $facet instanceof CategoryFacet => 'sp_category_path',
            $facet instanceof ContentSectionTypeFacet => 'sp_contenttype',
            $facet instanceof GroupFacet => 'sp_group_path',
            $facet instanceof ObjectTypeFacet => 'sp_objecttype',
            $facet instanceof SiteFacet => 'sp_site',
            $facet instanceof SourceFacet => 'sp_source',
            $facet instanceof ContentTypeFacet => 'contenttype',
            $facet instanceof RelativeDateRangeFacet, $facet instanceof AbsoluteDateRangeFacet => 'sp_date_list',
            default => throw new InvalidArgumentException(
                'Unsupported facet-field-class ' . get_class($facet),
            ),
        };
    }

    public function getArchiveField(): string
    {
        return 'sp_archive';
    }

    public function getGeoPointField(): string
    {
        return 'sp_geo_points';
    }


    public function getFilterField(Filter $filter): string
    {
        return match (true) {
            $filter instanceof IdFilter => 'id',
            $filter instanceof CategoryFilter => 'sp_category_path',
            $filter instanceof ContentSectionTypeFilter => 'sp_contenttype',
            $filter instanceof GroupFilter => 'sp_group_path',
            $filter instanceof ObjectTypeFilter => 'sp_objecttype',
            $filter instanceof SiteFilter => 'sp_site',
            $filter instanceof SourceFilter => 'sp_source',
            $filter instanceof ContentTypeFilter => 'contenttype',
            $filter instanceof RelativeDateRangeFilter, $filter instanceof AbsoluteDateRangeFilter => 'sp_date_list',
            $filter instanceof SpatialOrbitalFilter, $filter instanceof SpatialArbitraryRectangleFilter => $this->getGeoPointField(),
            $filter instanceof GeoLocatedFilter => 'sp_geo_points',
            default => throw new InvalidArgumentException(
                'Unsupported filter-field-class ' . get_class($filter),
            ),
        };
    }

    public function getSortField(Criteria $criteria): string
    {
        switch (true) {
            case $criteria instanceof Name:
                return 'sp_name';
            case $criteria instanceof Date:
                return 'sp_date';
            case $criteria instanceof Natural:
                return 'sp_sortvalue';
            case $criteria instanceof Score:
                return 'score';
            case $criteria instanceof SpatialDist:
                $params = [
                    $this->getGeoPointField(),
                    $criteria->point->lat,
                    $criteria->point->lng,
                ];
                return 'geodist(' . implode(',', $params) . ')';
            case $criteria instanceof CustomField:
                return $criteria->field;
            default:
                throw new InvalidArgumentException(
                    'Unsupported sort criteria: ' . get_class($criteria),
                );
        }
    }
}
