<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\Facet\AbsoluteDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Facet\FieldFacet;
use Atoolo\Search\Dto\Search\Query\Facet\MultiQueryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\QueryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\QueryTemplateFacet;
use Atoolo\Search\Dto\Search\Query\Facet\RelativeDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\SpatialDistanceRangeFacet;
use InvalidArgumentException;
use Solarium\Component\Facet\Field;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;

class SolrQueryFacetAppender
{
    public function __construct(
        private readonly SolrSelectQuery $solrQuery,
        private readonly Schema2xFieldMapper $fieldMapper,
        private readonly QueryTemplateResolver $queryTemplateResolver,
    ) {}

    public function append(Facet $facet): void
    {
        if ($facet instanceof FieldFacet) {
            $this->appendFacetField($facet);
        } elseif ($facet instanceof QueryFacet) {
            $this->appendFacetQuery($facet);
        } elseif ($facet instanceof MultiQueryFacet) {
            $this->appendFacetMultiQuery($facet);
        } elseif ($facet instanceof QueryTemplateFacet) {
            $this->appendQueryTemplateFacet($facet);
        } elseif ($facet instanceof AbsoluteDateRangeFacet) {
            $this->appendAbsoluteDateRangeFacet($facet);
        } elseif ($facet instanceof RelativeDateRangeFacet) {
            $this->appendRelativeDateRangeFacet($facet);
        } elseif ($facet instanceof SpatialDistanceRangeFacet) {
            $this->appendGeoDistanceRangeFacet($facet);

        } else {
            throw new InvalidArgumentException(
                'Unsupported facet-class ' . get_class($facet),
            );
        }
    }

    /**
     * https://solarium.readthedocs.io/en/stable/queries/select-query/building-a-select-query/components/facetset-component/facet-field/
     */
    private function appendFacetField(
        FieldFacet $facet,
    ): void {
        $facetSet = $this->solrQuery->getFacetSet();
        /** @var Field $solrFacet */
        $solrFacet = $facetSet->createFacetField($facet->key);
        $solrFacet->setMinCount(1);
        $solrFacet->setExcludes($facet->excludeFilter);
        $solrFacet->setField($this->getFacetField($facet));
        $solrFacet->setTerms($facet->terms);
    }

    private function getFacetField(Facet $facet): string
    {
        return $this->fieldMapper->getFacetField($facet);
    }

    /**
     * https://solarium.readthedocs.io/en/stable/queries/select-query/building-a-select-query/components/facetset-component/facet-query/
     */
    private function appendFacetQuery(
        QueryFacet $facet,
    ): void {
        $facetSet = $this->solrQuery->getFacetSet();
        $facetSet->createFacetQuery($facet->key)
            ->setQuery($facet->query)
            ->setExcludes($facet->excludeFilter);
    }

    /**
     * https://solarium.readthedocs.io/en/stable/queries/select-query/building-a-select-query/components/facetset-component/facet-multiquery/
     */
    private function appendFacetMultiQuery(
        MultiQueryFacet $facet,
    ): void {
        $facetSet = $this->solrQuery->getFacetSet();
        $solrFacet = $facetSet->createFacetMultiQuery($facet->key);
        $solrFacet->setExcludes($facet->excludeFilter);
        foreach ($facet->queries as $facetQuery) {
            $solrFacet->createQuery(
                $facetQuery->key,
                $facetQuery->query,
            );
        }
    }

    private function appendQueryTemplateFacet(QueryTemplateFacet $facet): void
    {
        $query = $this->queryTemplateResolver->resolve(
            $facet->query,
            $facet->variables,
        );
        $facetSet = $this->solrQuery->getFacetSet();
        $facetSet->createFacetQuery($facet->key)
            ->setQuery($query)
            ->setExcludes($facet->excludeFilter);
    }

    private function appendAbsoluteDateRangeFacet(
        AbsoluteDateRangeFacet $facet,
    ): void {
        $start = SolrDateMapper::mapDateTime($facet->from);
        $end = SolrDateMapper::mapDateTime($facet->to);
        $gap = $facet->gap !== null
            ? SolrDateMapper::mapDateInterval($facet->gap, '+')
            : null;
        $this->appendFacetRange($facet, $start, $end, $gap);
    }

    private function appendGeoDistanceRangeFacet(
        SpatialDistanceRangeFacet $facet,
    ): void {

        $params = [
            $this->fieldMapper->getGeoPointField(),
            $facet->point->lat,
            $facet->point->lng,
        ];

        $facetQuery = new QueryFacet(
            $facet->key,
            '{!frange l=' . $facet->from . ' u=' . $facet->to . '}geodist(' . implode(',', $params) . ')',
            $facet->excludeFilter,
        );
        $this->appendFacetQuery($facetQuery);
    }

    private function appendRelativeDateRangeFacet(
        RelativeDateRangeFacet $facet,
    ): void {

        $start = $facet->before === null
            ? SolrDateMapper::roundStart(
                SolrDateMapper::mapDateTime($facet->base),
                $facet->roundStart,
            )
            : SolrDateMapper::roundStart(
                SolrDateMapper::mapDateTime($facet->base) .
                    SolrDateMapper::mapDateInterval($facet->before, '-'),
                $facet->roundStart,
            );

        $end = $facet->after === null
            ? SolrDateMapper::roundEnd(
                SolrDateMapper::mapDateTime($facet->base),
                $facet->roundStart,
            )
            : SolrDateMapper::roundEnd(
                SolrDateMapper::mapDateTime($facet->base) .
                SolrDateMapper::mapDateInterval($facet->after, '+'),
                $facet->roundEnd,
            );

        $gap = $facet->gap !== null
            ? SolrDateMapper::mapDateInterval($facet->gap, '+')
            : null;
        $this->appendFacetRange($facet, $start, $end, $gap);
    }

    private function appendFacetRange(
        Facet $facet,
        string $start,
        string $end,
        ?string $gap,
    ): void {
        if ($gap === null) {
            // without `gap` it is a simple facet query
            $facetQuery = new QueryFacet(
                $facet->key,
                $this->getFacetField($facet) . ':' .
                '[' . $start . ' TO ' . $end . ']',
                $facet->excludeFilter,
            );
            $this->appendFacetQuery($facetQuery);
            return;
        }

        $facetSet = $this->solrQuery->getFacetSet();
        $solrFacet = $facetSet->createFacetRange($facet->key);
        $solrFacet->setMinCount(1);
        $solrFacet->setExcludes($facet->excludeFilter);
        $solrFacet
            ->setField($this->getFacetField($facet))
            ->setStart($start)
            ->setEnd($end)
            ->setGap($gap);
    }
}
