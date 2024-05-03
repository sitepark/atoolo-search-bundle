<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\Facet\AbsoluteDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\CategoryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ContentSectionTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Facet\FacetField;
use Atoolo\Search\Dto\Search\Query\Facet\FacetMultiQuery;
use Atoolo\Search\Dto\Search\Query\Facet\FacetQuery;
use Atoolo\Search\Dto\Search\Query\Facet\GroupFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ObjectTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\RelativeDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\SiteFacet;
use InvalidArgumentException;
use Solarium\Component\Facet\Field;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;

class SolrQueryFacetAppender
{
    public function __construct(
        private readonly SolrSelectQuery $solrQuery
    ) {
    }

    public function append(Facet $facet): void
    {
        $facet = $this->mapFacet($facet);

        if ($facet instanceof FacetField) {
            $this->addFacetFieldToSolrQuery($facet);
        } elseif ($facet instanceof FacetQuery) {
            $this->addFacetQueryToSolrQuery($facet);
        } elseif ($facet instanceof FacetMultiQuery) {
            $this->addFacetMultiQueryToSolrQuery($facet);
        } elseif ($facet instanceof SolrRangeFacet) {
            $this->addFacetRangeToSolrQuery($facet);
        } else {
            throw new InvalidArgumentException(
                'Unsupported facet-class ' . get_class($facet)
            );
        }
    }

    private function mapFacet(Facet $facet): Facet
    {
        switch (true) {
            case $facet instanceof AbsoluteDateRangeFacet:
                return new SolrAbsoluteDateRangeFacet(
                    $facet->key,
                    $facet->from,
                    $facet->to,
                    $facet->gap,
                    $facet->excludeFilter
                );
            case $facet instanceof RelativeDateRangeFacet:
                return new SolrRelativeDateRangeFacet(
                    $facet->key,
                    $facet->base,
                    $facet->before,
                    $facet->after,
                    $facet->gap,
                    $facet->roundStart,
                    $facet->roundEnd,
                    $facet->excludeFilter
                );
            default:
                return $facet;
        }
    }

    /**
     * https://solarium.readthedocs.io/en/stable/queries/select-query/building-a-select-query/components/facetset-component/facet-field/
     */
    private function addFacetFieldToSolrQuery(
        FacetField $facet
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
            case $facet instanceof SolrAbsoluteDateRangeFacet:
                return 'sp_date_list';
            case $facet instanceof SolrRelativeDateRangeFacet:
                return 'sp_date_list';
            default:
                throw new InvalidArgumentException(
                    'Unsupported facet-field-class ' . get_class($facet)
                );
        }
    }

    /**
     * https://solarium.readthedocs.io/en/stable/queries/select-query/building-a-select-query/components/facetset-component/facet-query/
     */
    private function addFacetQueryToSolrQuery(
        FacetQuery $facet
    ): void {
        $facetSet = $this->solrQuery->getFacetSet();
        $facetSet->createFacetQuery($facet->key)
            ->setQuery($facet->query)
            ->setExcludes($facet->excludeFilter);
    }

    /**
     * https://solarium.readthedocs.io/en/stable/queries/select-query/building-a-select-query/components/facetset-component/facet-multiquery/
     */
    private function addFacetMultiQueryToSolrQuery(
        FacetMultiQuery $facet
    ): void {
        $facetSet = $this->solrQuery->getFacetSet();
        $solrFacet = $facetSet->createFacetMultiQuery($facet->key);
        $solrFacet->setExcludes($facet->excludeFilter);
        foreach ($facet->queries as $facetQuery) {
            $solrFacet->createQuery(
                $facetQuery->key,
                $facetQuery->query
            );
        }
    }

    private function addFacetRangeToSolrQuery(
        SolrRangeFacet $facet
    ): void {

        if ($facet->getGap() === null) {
            // without `gap` it is a simple facet query
            $facetQuery = new FacetQuery(
                $facet->key,
                $this->getFacetField($facet) . ':' .
                '[' .
                $facet->getStart() .
                ' TO ' .
                $facet->getEnd() .
                ']',
                $facet->excludeFilter
            );
            $this->addFacetQueryToSolrQuery($facetQuery);
            return;
        }

        $facetSet = $this->solrQuery->getFacetSet();
        $solrFacet = $facetSet->createFacetRange($facet->key);
        $solrFacet->setMinCount(1);
        $solrFacet->setExcludes($facet->excludeFilter);
        $solrFacet
            ->setField($this->getFacetField($facet))
            ->setStart($facet->getStart())
            ->setEnd($facet->getEnd())
            ->setGap($facet->getGap());
    }
}
