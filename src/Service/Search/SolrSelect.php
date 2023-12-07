<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\Facet\FacetField;
use Atoolo\Search\Dto\Search\Query\Facet\FacetMultiQuery;
use Atoolo\Search\Dto\Search\Query\Facet\FacetQuery;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\QueryDefaultOperator;
use Atoolo\Search\Dto\Search\Query\SelectQuery;
use Atoolo\Search\Dto\Search\Result\Facet;
use Atoolo\Search\Dto\Search\Result\FacetGroup;
use Atoolo\Search\Dto\Search\Result\ResourceSearchResult;
use Atoolo\Search\SelectSearcher;
use Atoolo\Search\Service\SolrClientFactory;
use Solarium\Component\Result\Facet\FacetResultInterface;
use Solarium\Core\Client\Client;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;

/**
 * Implementation of the searcher on the basis of a Solr index.
 */
class SolrSelect implements SelectSearcher
{
    /**
     * @param iterable<SolrQueryModifier> $solrQueryModifierList
     */
    public function __construct(
        private readonly SolrClientFactory $clientFactory,
        private readonly iterable $solrQueryModifierList,
        private readonly SolrResultToResourceResolver $resultToResourceResolver
    ) {
    }

    public function select(SelectQuery $query): ResourceSearchResult
    {
        $client = $this->clientFactory->create($query->getIndex());

        $solrQuery = $this->buildSolrQuery($client, $query);
        $result = $client->execute($solrQuery);
        return $this->buildResult($query, $result);
    }

    private function buildSolrQuery(
        Client $client,
        SelectQuery $query
    ): SolrSelectQuery {

        $solrQuery = $client->createSelect();

        // supplements the query with standard values, e.g. for boosting
        foreach ($this->solrQueryModifierList as $solrQueryModifier) {
            $solrQuery = $solrQueryModifier->modify($solrQuery);
        }

        $solrQuery->setStart($query->getOffset());
        $solrQuery->setRows($query->getLimit());

        // to get query-time
        $solrQuery->setOmitHeader(false);

        $this->addRequiredFieldListToSolrQuery($solrQuery);
        $this->addTextFilterToSolrQuery($solrQuery, $query->getText());
        $this->addQueryDefaultOperatorToSolrQuery(
            $solrQuery,
            $query->getQueryDefaultOperator()
        );
        $this->addFilterQueriesToSolrQuery(
            $solrQuery,
            $query->getFilterList()
        );
        $this->addFacetListToSolrQuery(
            $solrQuery,
            $query->getFacetList()
        );

        return $solrQuery;
    }

    private function addRequiredFieldListToSolrQuery(
        SolrSelectQuery $solrQuery
    ): void {
        $solrQuery->setFields(['url']);
    }

    private function addTextFilterToSolrQuery(
        SolrSelectQuery $solrQuery,
        string $text
    ): void {
        if (empty($text)) {
            return;
        }
        $terms = explode(' ', $text);
        $terms = array_map(function ($term) use ($solrQuery) {
            $term = trim($term);
            return $solrQuery->getHelper()->escapeTerm($term);
        },
            $terms);
        $text = implode(' ', $terms);
        $solrQuery->setQuery($text);
    }

    private function addQueryDefaultOperatorToSolrQuery(
        SolrSelectQuery $solrQuery,
        QueryDefaultOperator $operator
    ): void {
        if ($operator === QueryDefaultOperator::OR) {
            $solrQuery->setQueryDefaultOperator(
                SolrSelectQuery::QUERY_OPERATOR_OR
            );
        } else {
            $solrQuery->setQueryDefaultOperator(
                SolrSelectQuery::QUERY_OPERATOR_AND
            );
        }
    }

    /**
     * @param Filter[] $filterList
     */
    private function addFilterQueriesToSolrQuery(
        SolrSelectQuery $solrQuery,
        array $filterList
    ): void {

        foreach ($filterList as $filter) {
            $key = $filter->getKey() ?? uniqid('', true);
            $solrQuery->createFilterQuery($key)
                ->setQuery($filter->getQuery())
                ->setTags($filter->getTags());
        }
    }

    /**
     * @param \Atoolo\Search\Dto\Search\Query\Facet\Facet[] $filterList
     */
    private function addFacetListToSolrQuery(
        SolrSelectQuery $solrQuery,
        array $facetList
    ): void {
        foreach ($facetList as $facet) {
            if ($facet instanceof FacetField) {
                $this->addFacetFieldToSolrQuery($solrQuery, $facet);
            } elseif ($facet instanceof FacetQuery) {
                $this->addFacetQueryToSolrQuery($solrQuery, $facet);
            } elseif ($facet instanceof FacetMultiQuery) {
                $this->addFacetMultiQueryToSolrQuery($solrQuery, $facet);
            } else {
                throw new \InvalidArgumentException(
                    'Unsupported facet-class ' . get_class($facet)
                );
            }
        }
    }

    /**
     * https://solarium.readthedocs.io/en/stable/queries/select-query/building-a-select-query/components/facetset-component/facet-field/
     */
    private function addFacetFieldToSolrQuery(
        SolrSelectQuery $solrQuery,
        FacetField $facet
    ): void {
        $facetSet = $solrQuery->getFacetSet();
        $field = $facet->getField();
        // https://solr.apache.org/guide/solr/latest/query-guide/faceting.html#tagging-and-excluding-filters
        if ($facet->getExcludeFilter() !== null) {
            $field = '{!ex=' . $facet->getExcludeFilter() . '}' . $field;
        }
        $facetSet->createFacetField($facet->getKey())
            ->setField($field)
            ->setTerms($facet->getTerms());
    }

    /**
     * https://solarium.readthedocs.io/en/stable/queries/select-query/building-a-select-query/components/facetset-component/facet-query/
     */
    private function addFacetQueryToSolrQuery(
        SolrSelectQuery $solrQuery,
        FacetQuery $facet
    ): void {
        $facetSet = $solrQuery->getFacetSet();
        $facetSet->createFacetQuery($facet->getKey())
            ->setQuery($facet->getQuery());
    }

    /**
     * https://solarium.readthedocs.io/en/stable/queries/select-query/building-a-select-query/components/facetset-component/facet-multiquery/
     */
    private function addFacetMultiQueryToSolrQuery(
        SolrSelectQuery $solrQuery,
        FacetMultiQuery $facet
    ): void {
        $facetSet = $solrQuery->getFacetSet();
        $solrFacet = $facetSet->createFacetMultiQuery($facet->getKey());
        foreach ($facet->getQueryList() as $facetQuery) {
            $solrFacet->createQuery(
                $facetQuery->getKey(),
                $facetQuery->getQuery()
            );
        }
    }

    private function buildResult(
        SelectQuery $query,
        ResultInterface $result
    ): ResourceSearchResult {

        $resourceList = $this->resultToResourceResolver
            ->loadResourceList($result);
        $facetGroupList = $this->buildFacetGroupList($query, $result);

        return new ResourceSearchResult(
            $result->getNumFound(),
            $query->getLimit(),
            $query->getOffset(),
            $resourceList,
            $facetGroupList,
            $result->getQueryTime()
        );
    }

    /**
     * @param ResultInterface $result
     * @return FacetGroup[]
     */
    private function buildFacetGroupList(
        SelectQuery $query,
        ResultInterface $result
    ): array {

        $facetSet = $result->getFacetSet();
        if ($facetSet === null) {
            return [];
        }

        $facetGroupList = [];
        foreach ($query->getFacetList() as $facet) {
            $facetGroupList[] = $this->buildFacetGroup(
                $facet->getKey(),
                $facetSet->getFacet($facet->getKey())
            );
        }
        return $facetGroupList;
    }

    private function buildFacetGroup(
        string $key,
        FacetResultInterface $solrFacet
    ): FacetGroup {
        $facetList = [];
        foreach ($solrFacet as $value => $count) {
            $facetList[] = new Facet((string)$value, $count);
        }
        return new FacetGroup($key, $facetList);
    }
}
