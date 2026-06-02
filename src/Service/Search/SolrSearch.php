<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Search;
use Atoolo\Search\Service\IndexName;
use Atoolo\Search\Service\SolrClientFactory;
use Solarium\Core\Client\Client;
use Solarium\QueryType\Select\Result\Result as SelectResult;

/**
 * Implementation of the searcher on the basis of a Solr index.
 */
class SolrSearch implements Search
{
    public const QUERY_FIELDS_REQUIRED = [
        'url',
        'title',
        'description',
        'id',
        'sp_id',
        'sp_objecttype',
        'sp_date',
        'sp_date_from',
        'sp_date_to',
    ];

    public function __construct(
        private readonly IndexName $index,
        private readonly SolrClientFactory $clientFactory,
        private readonly SolrQueryBuilder $queryBuilder,
        private readonly SolrResultBuilder $resultBuilder,
    ) {}

    public function search(SearchQuery $query): SearchResult
    {
        $index = $this->index->name($query->lang);
        $client = $this->clientFactory->create($index);

        /**
         * 'expandByDate' has to search in root AND nested documents,
         * whereas all other searches only search within root documents.
         */
        if ($query->expandByDate) {
            /*
            * 1. search parents with child-filter, parent-filter and parent-facetts
            * 2. search children with parent-filter, child-date-filter and child-date-facetts
            * 2. Merge all facets and return children (with merged parent-fields)
            */
            $solrParentQuery = $this->queryBuilder->buildDateParentQuery($client, $query);
            /** @var SelectResult $solrParentsResult */
            $solrParentsResult = $client->execute($solrParentQuery);
            $parentResult = $this->resultBuilder->buildResult($query, $solrParentsResult, $query->lang);
            $parentSearchFacetGroups = $parentResult->facetGroups;

            $solrChildrenQuery = $this->queryBuilder->buildDateChildQuery($client, $query);
            /** @var SelectResult $solrChildrenResult */
            $solrChildrenResult = $client->execute($solrChildrenQuery);
            $childSearchResult = $this->resultBuilder->buildResult($query, $solrChildrenResult, $query->lang);
            $childSearchFacetGroups = $childSearchResult->facetGroups;

            return $this->resultBuilder->buildExpandedResult(
                $query,
                $solrParentsResult,
                $solrChildrenResult,
                $parentSearchFacetGroups,
                $childSearchFacetGroups,
                $query->lang,
            );
        } else {
            $solrQuery = $this->queryBuilder->buildDefaultQuery($client, $query);
            /** @var SelectResult $result */
            $result = $client->execute($solrQuery);
            return $this->resultBuilder->buildResult($query, $result, $query->lang);
        }
    }
}
