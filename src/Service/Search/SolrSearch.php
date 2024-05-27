<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Boosting;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\QueryOperator;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Query\Sort\Criteria;
use Atoolo\Search\Dto\Search\Result\Facet;
use Atoolo\Search\Dto\Search\Result\FacetGroup;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Search;
use Atoolo\Search\Service\IndexName;
use Atoolo\Search\Service\Search\SiteKit\DefaultBoosting;
use Atoolo\Search\Service\SolrClientFactory;
use InvalidArgumentException;
use Solarium\Component\Result\Facet\Field as SolrFacetField;
use Solarium\Component\Result\Facet\Query as SolrFacetQuery;
use Solarium\Core\Client\Client;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;
use Solarium\QueryType\Select\Result\Result as SelectResult;

/**
 * Implementation of the searcher on the basis of a Solr index.
 */
class SolrSearch implements Search
{
    /**
     * @param iterable<SolrQueryModifier> $solrQueryModifierList
     */
    public function __construct(
        private readonly IndexName $index,
        private readonly SolrClientFactory $clientFactory,
        private readonly SolrResultToResourceResolver $resultToResourceResolver,
        private readonly Schema2xFieldMapper $schemaFieldMapper,
        private readonly iterable $solrQueryModifierList = []
    ) {
    }

    public function search(SearchQuery $query): SearchResult
    {
        $index = $this->index->name($query->lang);
        $client = $this->clientFactory->create($index);

        $solrQuery = $this->buildSolrQuery($client, $query);
        /** @var SelectResult $result */
        $result = $client->execute($solrQuery);
        return $this->buildResult($query, $result, $query->lang);
    }

    private function buildSolrQuery(
        Client $client,
        SearchQuery $query
    ): SolrSelectQuery {

        $solrQuery = $client->createSelect();

        // supplements the query with standard values, e.g. for boosting
        foreach ($this->solrQueryModifierList as $solrQueryModifier) {
            $solrQuery = $solrQueryModifier->modify($solrQuery);
        }

        $solrQuery->setStart($query->offset);
        $solrQuery->setRows($query->limit);

        // to get query-time
        $solrQuery->setOmitHeader(false);

        $this->addSortToSolrQuery($solrQuery, $query->sort);
        $this->addRequiredFieldListToSolrQuery($solrQuery);
        $this->addTextFilterToSolrQuery($solrQuery, $query->text);
        $this->addQueryDefaultOperatorToSolrQuery(
            $solrQuery,
            $query->defaultQueryOperator
        );
        $this->addFilterQueriesToSolrQuery(
            $solrQuery,
            $query->filter,
            $query->archive
        );
        $this->addFacetListToSolrQuery(
            $solrQuery,
            $query->facets
        );

        if ($query->timeZone !== null) {
            $solrQuery->setTimezone($query->timeZone);
        } elseif (date_default_timezone_get()) {
            $solrQuery->setTimezone(date_default_timezone_get());
        }

        $this->addBoosting($solrQuery, $query->boosting);

        return $solrQuery;
    }

    /**
     * @param Criteria[] $criteriaList
     */
    private function addSortToSolrQuery(
        SolrSelectQuery $solrQuery,
        array $criteriaList
    ): void {

        $sorts = [];
        foreach ($criteriaList as $criteria) {
            $field = $this->schemaFieldMapper->getSortField($criteria);
            $direction = strtolower($criteria->direction->name);
            $sorts[$field] = $direction;
        }
        $solrQuery->setSorts($sorts);
    }

    private function addRequiredFieldListToSolrQuery(
        SolrSelectQuery $solrQuery
    ): void {
        $solrQuery->setFields([
            'url',
            'title',
            'description',
            'sp_id',
            'sp_objecttype'
        ]);
    }

    private function addTextFilterToSolrQuery(
        SolrSelectQuery $solrQuery,
        string $text
    ): void {
        if (empty($text)) {
            return;
        }
        $terms = explode(' ', $text);
        $terms = array_map(
            static fn ($term) =>
                $solrQuery->getHelper()->escapeTerm(trim($term)),
            $terms
        );
        $text = implode(' ', $terms);
        $solrQuery->setQuery($text);
    }

    private function addQueryDefaultOperatorToSolrQuery(
        SolrSelectQuery $solrQuery,
        QueryOperator $operator
    ): void {
        $solrQuery->setQueryDefaultOperator(
            $operator === QueryOperator::OR
                ? SolrSelectQuery::QUERY_OPERATOR_OR
                : SolrSelectQuery::QUERY_OPERATOR_AND
        );
    }

    /**
     * @param Filter[] $filterList
     */
    private function addFilterQueriesToSolrQuery(
        SolrSelectQuery $solrQuery,
        array $filterList,
        bool $archive
    ): void {
        $filterAppender = new SolrQueryFilterAppender(
            $solrQuery,
            $this->schemaFieldMapper
        );
        foreach ($filterList as $filter) {
            $filterAppender->append($filter);
        }
        if (!$archive) {
            $filterAppender->excludeArchived();
        }
    }

    /**
     * @param \Atoolo\Search\Dto\Search\Query\Facet\Facet[] $facetList
     */
    private function addFacetListToSolrQuery(
        SolrSelectQuery $solrQuery,
        array $facetList
    ): void {
        $facetAppender = new SolrQueryFacetAppender(
            $solrQuery,
            $this->schemaFieldMapper
        );
        foreach ($facetList as $facet) {
            $facetAppender->append($facet);
        }
    }

    private function addBoosting(
        SolrSelectQuery $solrQuery,
        ?Boosting $boosting
    ): void {
        $boosting = $boosting ?? new DefaultBoosting();

        $edismax = $solrQuery->getEDisMax();
        if (!empty($boosting->queryFields)) {
            $edismax->setQueryFields(
                implode(' ', $boosting->queryFields)
            );
        }
        if (!empty($boosting->phraseFields)) {
            $edismax->setPhraseFields(
                implode(' ', $boosting->phraseFields)
            );
        }
        if (!empty($boosting->boostQueries)) {
            $edismax->setBoostQueries($boosting->boostQueries);
        }
        if (!empty($boosting->boostFunctions)) {
            $edismax->setBoostFunctions(
                implode(' ', $boosting->boostFunctions)
            );
        }
        if ($boosting->tie > 0.0) {
            $edismax->setTie($boosting->tie);
        }
    }

    private function buildResult(
        SearchQuery $query,
        SelectResult $result,
        ResourceLanguage $lang
    ): SearchResult {

        $resourceList = $this->resultToResourceResolver
            ->loadResourceList($result, $lang);
        $facetGroupList = $this->buildFacetGroupList($query, $result);

        return new SearchResult(
            total:$result->getNumFound() ?? 0,
            limit: $query->limit,
            offset: $query->offset,
            results: $resourceList,
            facetGroups: $facetGroupList,
            queryTime: $result->getQueryTime() ?? 0
        );
    }

    /**
     * @return FacetGroup[]
     */
    private function buildFacetGroupList(
        SearchQuery $query,
        SelectResult $result
    ): array {

        $facetSet = $result->getFacetSet();
        if ($facetSet === null) {
            return [];
        }

        $facetGroupList = [];
        foreach ($query->facets as $facet) {
            $resultFacet = $facetSet->getFacet($facet->key);
            if ($resultFacet === null) {
                continue;
            }
            if (
                $resultFacet instanceof SolrFacetField
            ) {
                $facetGroupList[] = $this->buildFacetGroupByField(
                    $facet->key,
                    $resultFacet
                );
            }

            if (
                $resultFacet instanceof SolrFacetQuery
            ) {
                $facetGroupList[] = $this->buildFacetGroupByQuery(
                    $facet->key,
                    $resultFacet
                );
            }
        }
        return $facetGroupList;
    }

    private function buildFacetGroupByField(
        string $key,
        SolrFacetField $solrFacet
    ): FacetGroup {
        $facetList = [];
        foreach ($solrFacet as $value => $count) {
            if (!is_int($count)) {
                throw new InvalidArgumentException(
                    'facet count should be a int: ' . $count
                );
            }
            $facetList[] = new Facet((string)$value, $count);
        }
        return new FacetGroup($key, $facetList);
    }

    private function buildFacetGroupByQuery(
        string $key,
        SolrFacetQuery $solrFacet
    ): FacetGroup {
        $facetList = [];

        $value = $solrFacet->getValue();
        $value = is_int($value) ? $value : 0;

        $facetList[] = new Facet($key, $value);
        return new FacetGroup($key, $facetList);
    }
}
