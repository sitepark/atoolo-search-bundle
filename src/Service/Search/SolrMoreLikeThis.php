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
        private readonly Schema2xFieldMapper $schemaFieldMapper,
        private readonly QueryTemplateResolver $queryTemplateResolver,
    ) {}

    public function moreLikeThis(MoreLikeThisQuery $query): SearchResult
    {
        $index = $this->index->name($query->lang);
        $client = $this->clientFactory->create($index);
        $solrQuery = $this->buildSolrQuery($client, $query);
        /** @var SolrMoreLikeThisResult $result */
        $result = $client->execute($solrQuery);
        return $this->buildResult($result, $query->lang);
    }

    private function buildSolrQuery(
        Client $client,
        MoreLikeThisQuery $query,
    ): SolrMoreLikeThisQuery {

        $solrQuery = $client->createMoreLikeThis();
        $solrQuery->setOmitHeader(false);
        $solrQuery->setQuery('id:' . $query->id);
        $solrQuery->setMltFields($query->fields);
        $solrQuery->setRows($query->limit);
        $solrQuery->setMinimumTermFrequency(2);
        $solrQuery->setMatchInclude(true);

        // Filter
        $filterQuery = $solrQuery->createFilterQuery('self');
        $filterQuery->setQuery('-id:' . $query->id);
        $this->addFilterQueriesToSolrQuery($solrQuery, $query->filter, $query->archive);

        return $solrQuery;
    }

    /**
     * @param Filter[] $filterList
     */
    private function addFilterQueriesToSolrQuery(
        SolrMoreLikeThisQuery $solrQuery,
        array $filterList,
        bool $archive,
    ): void {
        $filterAppender = new SolrQueryFilterAppender(
            $solrQuery,
            $this->schemaFieldMapper,
            $this->queryTemplateResolver,
        );
        foreach ($filterList as $filter) {
            $filterAppender->append($filter);
        }
        if (!$archive) {
            $filterAppender->excludeArchived();
        }
    }

    private function buildResult(
        SolrMoreLikeThisResult $result,
        ResourceLanguage $lang,
    ): SearchResult {

        $resourceList = $this->resultToResourceResolver
            ->loadResourceList($result, $lang);

        return new SearchResult(
            total: $result->getNumFound() ?? 0,
            limit: 0,
            offset: 0,
            results: $resourceList,
            facetGroups: [],
            spellcheck: null,
            queryTime: $result->getQueryTime() ?? 0,
        );
    }
}
