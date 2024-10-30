<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\Filter\AbsoluteDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\AndFilter;
use Atoolo\Search\Dto\Search\Query\Filter\FieldFilter;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\Filter\NotFilter;
use Atoolo\Search\Dto\Search\Query\Filter\OrFilter;
use Atoolo\Search\Dto\Search\Query\Filter\QueryFilter;
use Atoolo\Search\Dto\Search\Query\Filter\RelativeDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialArbitraryRectangleFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialOrbitalFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialOrbitalMode;
use InvalidArgumentException;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;

class SolrQueryFilterAppender
{
    public function __construct(
        private readonly SolrSelectQuery $solrQuery,
        private readonly Schema2xFieldMapper $fieldMapper,
    ) {}

    public function excludeArchived(): void
    {
        $filterQuery = $this->solrQuery->createFilterQuery();
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
        switch (true) {
            case $filter instanceof FieldFilter:
                return $this->getFieldQuery($filter);
            case $filter instanceof AndFilter:
                return $this->getAndQuery($filter);
            case $filter instanceof OrFilter:
                return $this->getOrQuery($filter);
            case $filter instanceof NotFilter:
                return 'NOT ' . $this->getQuery($filter->filter);
            case $filter instanceof QueryFilter:
                return $filter->query;
            case $filter instanceof AbsoluteDateRangeFilter:
                return $this->getAbsoluteDateRangeQuery($filter);
            case $filter instanceof RelativeDateRangeFilter:
                return $this->getRelativeDateRangeQuery($filter);
            case $filter instanceof SpatialOrbitalFilter:
                return $this->getSpatialOrbitalQuery($filter);
            case $filter instanceof SpatialArbitraryRectangleFilter:
                return $this->getSpatialArbitraryRectangleQuery($filter);
            default:
                throw new InvalidArgumentException(
                    'unsupported filter ' . get_class($filter),
                );
        }
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

    private function getFieldQuery(FieldFilter $filter): string
    {
        $field = $this->getFilterField($filter);
        $filterValue = count($filter->values) === 1
            ? $filter->values[0]
            : '(' . implode(' ', $filter->values) . ')';
        return $field . ':' . $filterValue;
    }

    private function getFilterField(Filter $filter): string
    {
        return $this->fieldMapper->getFilterField($filter);
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

        if ($filter->before === null) {
            $from = SolrDateMapper::roundStart(
                SolrDateMapper::mapDateTime($filter->base),
                $filter->roundStart,
            );
        } else {
            $from = SolrDateMapper::roundStart(
                SolrDateMapper::mapDateTime($filter->base) .
                SolrDateMapper::mapDateInterval($filter->before, '-'),
                $filter->roundStart,
            );
        }

        if ($filter->after === null) {
            $to = SolrDateMapper::roundEnd(
                SolrDateMapper::mapDateTime($filter->base),
                $filter->roundEnd,
            );
        } else {
            $to = SolrDateMapper::roundEnd(
                SolrDateMapper::mapDateTime($filter->base) .
                SolrDateMapper::mapDateInterval($filter->after, '+'),
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
