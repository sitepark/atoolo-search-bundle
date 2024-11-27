<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\Facet\AbsoluteDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\CategoryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ContentSectionTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Facet\GroupFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ObjectTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\RelativeDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\SiteFacet;
use Atoolo\Search\Dto\Search\Query\Filter\AbsoluteDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ArchiveFilter;
use Atoolo\Search\Dto\Search\Query\Filter\CategoryFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ContentSectionTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\Filter\GroupFilter;
use Atoolo\Search\Dto\Search\Query\Filter\IdFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ObjectTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\RelativeDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SiteFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialArbitraryRectangleFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialOrbitalFilter;
use Atoolo\Search\Dto\Search\Query\Sort\Criteria;
use Atoolo\Search\Dto\Search\Query\Sort\CustomField;
use Atoolo\Search\Dto\Search\Query\Sort\Date;
use Atoolo\Search\Dto\Search\Query\Sort\Headline;
use Atoolo\Search\Dto\Search\Query\Sort\Name;
use Atoolo\Search\Dto\Search\Query\Sort\Natural;
use Atoolo\Search\Dto\Search\Query\Sort\Score;
use Atoolo\Search\Dto\Search\Query\Sort\SpatialDist;
use InvalidArgumentException;

class Schema2xFieldMapper
{
    public function getFacetField(Facet $facet): string
    {
        switch (true) {
            case $facet instanceof CategoryFacet:
                return 'sp_category_path';
            case $facet instanceof ContentSectionTypeFacet:
                return 'sp_contenttype';
            case $facet instanceof GroupFacet:
                return 'sp_group_path';
            case $facet instanceof ObjectTypeFacet:
                return 'sp_objecttype';
            case $facet instanceof SiteFacet:
                return 'sp_site';
            case $facet instanceof RelativeDateRangeFacet:
            case $facet instanceof AbsoluteDateRangeFacet:
                return 'sp_date_list';
            default:
                throw new InvalidArgumentException(
                    'Unsupported facet-field-class ' . get_class($facet),
                );
        }
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
        switch (true) {
            case $filter instanceof IdFilter:
                return 'id';
            case $filter instanceof CategoryFilter:
                return 'sp_category_path';
            case $filter instanceof ContentSectionTypeFilter:
                return 'sp_contenttype';
            case $filter instanceof GroupFilter:
                return 'sp_group_path';
            case $filter instanceof ObjectTypeFilter:
                return 'sp_objecttype';
            case $filter instanceof SiteFilter:
                return 'sp_site';
            case $filter instanceof RelativeDateRangeFilter:
            case $filter instanceof AbsoluteDateRangeFilter:
                return 'sp_date_list';
            case $filter instanceof SpatialOrbitalFilter:
            case $filter instanceof SpatialArbitraryRectangleFilter:
                return $this->getGeoPointField();
            default:
                throw new InvalidArgumentException(
                    'Unsupported filter-field-class ' . get_class($filter),
                );
        }
    }

    public function getSortField(Criteria $criteria): string
    {
        switch (true) {
            case $criteria instanceof Name:
                return 'sp_name';
            case $criteria instanceof Headline:
                return 'sp_sortvalue';
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
