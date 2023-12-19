<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\MoreLikeThisQuery;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\MoreLikeThisSearcher;
use Atoolo\Search\Service\SolrClientFactory;
use Solarium\Core\Client\Client;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\MoreLikeThis\Query as SolrMoreLikeThisQuery;

/**
 * Implementation of the "More-Like-This" on the basis of a Solr index.
 */
class SolrMoreLikeThis implements MoreLikeThisSearcher
{
    /**
     * @param iterable<ResourceFactory> $resourceFactoryList
     */
    public function __construct(
        private readonly SolrClientFactory $clientFactory,
        private readonly SolrResultToResourceResolver $resultToResourceResolver
    ) {
    }

    public function moreLikeThis(MoreLikeThisQuery $query): SearchResult
    {
        $client = $this->clientFactory->create($query->getCore());
        $solrQuery = $this->buildSolrQuery($client, $query);
        $result = $client->execute($solrQuery);
        return $this->buildResult($result);
    }

    private function buildSolrQuery(
        Client $client,
        MoreLikeThisQuery $query
    ): SolrMoreLikeThisQuery {

        $solrQuery = $client->createMoreLikeThis();
        $solrQuery->setOmitHeader(false);
        $solrQuery->setQuery('url:"' . $query->getLocation() . '"');
        $solrQuery->setMltFields($query->getFieldList());
        $solrQuery->setRows($query->getLimit());
        $solrQuery->setMinimumTermFrequency(2);
        $solrQuery->setMatchInclude(true);
        $solrQuery->createFilterQuery('nomedia')
            ->setQuery('-sp_objecttype:media');

        // Filter
        foreach ($query->getFilterList() as $filter) {
            $solrQuery->createFilterQuery($filter->getKey())
                ->setQuery($filter->getQuery())
                ->setTags($filter->getTags());
        }

        return $solrQuery;
    }

    private function buildResult(
        ResultInterface $result
    ): SearchResult {

        $resourceList = $this->resultToResourceResolver
            ->loadResourceList($result);

        return new SearchResult(
            $result->getNumFound(),
            0,
            $resourceList,
            [],
            $result->getQueryTime()
        );
    }
}
