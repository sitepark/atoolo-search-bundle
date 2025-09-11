<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\Filter\AbsoluteDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\AndFilter;
use Atoolo\Search\Dto\Search\Query\Filter\FieldFilter;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\Filter\GeoLocatedFilter;
use Atoolo\Search\Dto\Search\Query\Filter\NotFilter;
use Atoolo\Search\Dto\Search\Query\Filter\OrFilter;
use Atoolo\Search\Dto\Search\Query\Filter\QueryFilter;
use Atoolo\Search\Dto\Search\Query\Filter\QueryTemplateFilter;
use Atoolo\Search\Dto\Search\Query\Filter\RelativeDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialArbitraryRectangleFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialOrbitalFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialOrbitalMode;
use Atoolo\Search\Dto\Search\Query\Filter\TeaserPropertyFilter;
use InvalidArgumentException;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;

class SolrQueryFilterAppender
{
    public function __construct(
        private readonly SolrSelectQuery $solrQuery,
        private readonly Schema2xFieldMapper $fieldMapper,
        private readonly QueryTemplateResolver $queryTemplateResolver,
    ) {}

    public function excludeArchived(): void
    {
        $filterQuery = $this->solrQuery->createFilterQuery('archive');
        $field = $this->fieldMapper->getArchiveField();
        $filterQuery->setQuery('-' . $field . ':true');
    }

    public function append(Filter $filter): void
    {
        $key = $filter->key ?? uniqid('', true);
        $filterQuery = $this->solrQuery->createFilterQuery($key);
        $filterQuery->setQuery($this->getQuery($filter));
        $filterQuery->setTags(array_merge($filter->tags, [$key]));
    }

    private function getQuery(Filter $filter): string
    {
        return match (true) {
            $filter instanceof FieldFilter => $this->getFieldQuery($filter),
            $filter instanceof AndFilter => $this->getAndQuery($filter),
            $filter instanceof OrFilter => $this->getOrQuery($filter),
            $filter instanceof NotFilter => 'NOT ' . $this->getQuery($filter->filter),
            $filter instanceof QueryFilter => $filter->query,
            $filter instanceof QueryTemplateFilter => $this->getQueryTemplateFilter($filter),
            $filter instanceof TeaserPropertyFilter => $this->getTeaserPropertyFilter($filter),
            $filter instanceof AbsoluteDateRangeFilter => $this->getAbsoluteDateRangeQuery($filter),
            $filter instanceof RelativeDateRangeFilter => $this->getRelativeDateRangeQuery($filter),
            $filter instanceof SpatialOrbitalFilter => $this->getSpatialOrbitalQuery($filter),
            $filter instanceof SpatialArbitraryRectangleFilter => $this->getSpatialArbitraryRectangleQuery($filter),
            $filter instanceof GeoLocatedFilter => $this->getGeoLocatedQuery($filter),
            default => throw new InvalidArgumentException(
                'unsupported filter ' . get_class($filter),
            ),
        };
    }

    private function getAndQuery(AndFilter $andFilter): string
    {
        $query = [];
        foreach ($andFilter->filter as $filter) {
            $query[] = $this->getQuery($filter);
        }

        return '(' . implode(' AND ', $query) . ')';
    }

    private function getOrQuery(OrFilter $orFilter): string
    {
        $query = [];
        foreach ($orFilter->filter as $filter) {
            $query[] = $this->getQuery($filter);
        }

        return '(' . implode(' OR ', $query) . ')';
    }

    private function getGeoLocatedQuery(GeoLocatedFilter $filter): string
    {
        $field = $this->getFilterField($filter);
        return ($filter->exists ? '' : '-') . $field . ':*';
    }

    private function getFieldQuery(FieldFilter $filter): string
    {
        $field = $this->getFilterField($filter);
        $values = array_map(
            static function (string $value) {
                $value = trim($value);
                if (empty($value)) {
                    return '["" TO *]';
                }
                return $value;
            },
            $filter->values,
        );
        $filterValue = count($values) === 1
            ? $values[0]
            : '(' . implode(' ', $values) . ')';
        return $field . ':' . $filterValue;
    }

    private function getFilterField(Filter $filter): string
    {
        return $this->fieldMapper->getFilterField($filter);
    }

    private function getQueryTemplateFilter(QueryTemplateFilter $filter): string
    {
        return $this->queryTemplateResolver->resolve(
            $filter->query,
            $filter->variables,
        );
    }

    private function getTeaserPropertyFilter(TeaserPropertyFilter $filter): string
    {
        $field = $this->fieldMapper->getFilterField($filter);
        $query = [];
        if ($filter->image !== null) {
            $query[] = (!$filter->image ? '-' : '') . $field . ':teaserImage';
        }
        if ($filter->imageCopyright !== null) {
            $query[] = (!$filter->imageCopyright ? '-' : '') . $field . ':teaserImageCopyright';
        }
        if ($filter->headline !== null) {
            $query[] = (!$filter->headline ? '-' : '') . $field . ':teaserHeadline';
        }
        if ($filter->text !== null) {
            $query[] = (!$filter->text ? '-' : '') . $field . ':teaserText';
        }
        if (empty($query)) {
            return '';
        }
        return '(' . implode(' AND ', $query) . ')';
    }

    private function getAbsoluteDateRangeQuery(
        AbsoluteDateRangeFilter $filter,
    ): string {
        $field = $this->getFilterField($filter);
        $from = SolrDateMapper::mapDateTime($filter->from, '*');
        $to = SolrDateMapper::mapDateTime($filter->to, '*');
        return $field . ':' . '[' . $from . ' TO ' . $to . ']';
    }

    private function getRelativeDateRangeQuery(
        RelativeDateRangeFilter $filter,
    ): string {
        if ($filter->from === null) {
            $from = SolrDateMapper::roundStart(
                SolrDateMapper::mapDateTime($filter->base),
                $filter->roundStart,
            );
        } else {
            $from = SolrDateMapper::roundStart(
                SolrDateMapper::mapDateTime($filter->base) .
                    SolrDateMapper::mapDateInterval(
                        $filter->from,
                        $filter->from->invert === 1 ? '-' : '+',
                    ),
                $filter->roundStart,
            );
        }
        if ($filter->to === null) {
            $to = SolrDateMapper::roundEnd(
                SolrDateMapper::mapDateTime($filter->base),
                $filter->roundEnd,
            );
        } else {
            $to = SolrDateMapper::roundEnd(
                SolrDateMapper::mapDateTime($filter->base) .
                    SolrDateMapper::mapDateInterval(
                        $filter->to,
                        $filter->to->invert === 1 ? '-' : '+',
                    ),
                $filter->roundEnd,
            );
        }

        $field = $this->getFilterField($filter);

        return $field . ':[' . $from . ' TO ' . $to . ']';
    }

    private function getSpatialOrbitalQuery(
        SpatialOrbitalFilter $filter,
    ): string {

        $field = $this->getFilterField($filter);
        $params = [
            'sfield=' . $field,
            'pt=' . $filter->centerPoint->lat . ',' . $filter->centerPoint->lng,
            'd=' . $filter->distance,
        ];

        if ($filter->mode === SpatialOrbitalMode::GREAT_CIRCLE_DISTANCE) {
            return '{!geofilt ' . implode(' ', $params) . '}';
        }

        if ($filter->mode === SpatialOrbitalMode::BOUNDING_BOX) {
            return '{!bbox ' . implode(' ', $params) . '}';
        }
    }

    private function getSpatialArbitraryRectangleQuery(
        SpatialArbitraryRectangleFilter $filter,
    ): string {

        $field = $this->getFilterField($filter);

        return $field
            . ':[ '
            . $filter->lowerLeftCorner->lat
            . ','
            . $filter->lowerLeftCorner->lng
            . ' TO '
            . $filter->upperRightCorner->lat
            . ','
            . $filter->upperRightCorner->lng
            . ' ]';
    }
}
