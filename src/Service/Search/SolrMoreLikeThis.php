<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\MoreLikeThisQuery;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\MoreLikeThis;
use Atoolo\Search\Service\IndexName;
use Atoolo\Search\Service\SolrClientFactory;
use Solarium\Core\Client\Client;
use Solarium\QueryType\MoreLikeThis\Query as SolrMoreLikeThisQuery;
use Solarium\QueryType\MoreLikeThis\Result as SolrMoreLikeThisResult;

/**
 * Implementation of the "More-Like-This" on the basis of a Solr index.
 */
class SolrMoreLikeThis implements MoreLikeThis
{
    public function __construct(
        private readonly IndexName $index,
        private readonly SolrClientFactory $clientFactory,
        private readonly SolrResultToResourceResolver $resultToResourceResolver,
        private readonly Schema2xFieldMapper $schemaFieldMapper
    ) {
    }

    public function moreLikeThis(MoreLikeThisQuery $query): SearchResult
    {
        $index = $this->index->name($query->location->lang);
        $client = $this->clientFactory->create($index);
        $solrQuery = $this->buildSolrQuery($client, $query);
        /** @var SolrMoreLikeThisResult $result */
        $result = $client->execute($solrQuery);
        return $this->buildResult($result, $query->location->lang);
    }

    private function buildSolrQuery(
        Client $client,
        MoreLikeThisQuery $query
    ): SolrMoreLikeThisQuery {

        $solrQuery = $client->createMoreLikeThis();
        $solrQuery->setOmitHeader(false);
        $solrQuery->setQuery('url:"' . $query->location . '"');
        $solrQuery->setMltFields($query->fields);
        $solrQuery->setRows($query->limit);
        $solrQuery->setMinimumTermFrequency(2);
        $solrQuery->setMatchInclude(true);

        // Filter
        $this->addFilterQueriesToSolrQuery($solrQuery, $query->filter);

        return $solrQuery;
    }

    /**
     * @param Filter[] $filterList
     */
    private function addFilterQueriesToSolrQuery(
        SolrMoreLikeThisQuery $solrQuery,
        array $filterList
    ): void {
        $filterAppender = new SolrQueryFilterAppender(
            $solrQuery,
            $this->schemaFieldMapper
        );
        foreach ($filterList as $filter) {
            $filterAppender->append($filter);
        }
    }

    private function buildResult(
        SolrMoreLikeThisResult $result,
        ResourceLanguage $lang
    ): SearchResult {

        $resourceList = $this->resultToResourceResolver
            ->loadResourceList($result, $lang);

        return new SearchResult(
            total: $result->getNumFound() ?? 0,
            limit: 0,
            offset: 0,
            results: $resourceList,
            facetGroups: [],
            queryTime: $result->getQueryTime() ?? 0
        );
    }
}
