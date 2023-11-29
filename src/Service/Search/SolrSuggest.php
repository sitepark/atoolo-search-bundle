<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\SuggestQuery;
use Atoolo\Search\Dto\Search\Result\Suggestion;
use Atoolo\Search\Dto\Search\Result\SuggestResult;
use Atoolo\Search\Exception\UnexpectedResultException;
use Atoolo\Search\Service\SolrParameterClientFactory;
use Atoolo\Search\SuggestSearcher;
use Solarium\Core\Client\Client;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;
use Solarium\QueryType\Select\Result\Result as SolrSelectResult;
use JsonException;

/**
 * Implementation of the "suggest search" based on a Solr index.
 */
class SolrSuggest implements SuggestSearcher
{
    public function __construct(
        private readonly SolrParameterClientFactory $clientFactory
    ) {
    }

    /**
     * @throws UnexpectedResultException
     */
    public function suggest(SuggestQuery $query): SuggestResult
    {
        $client = $this->clientFactory->create($query->getCore());

        $solrQuery = $this->buildSolrQuery($client, $query);
        $solrResult = $client->select($solrQuery);
        return $this->buildResult($solrResult, $query->getField());
    }

    private function buildSolrQuery(
        Client $client,
        SuggestQuery $query
    ): SolrSelectQuery {
        $solrQuery = $client->createSelect();
        $solrQuery->addParam("spellcheck", "true");
        $solrQuery->addParam("spellcheck.accuracy", "0.6");
        $solrQuery->addParam("spellcheck.onlyMorePopular", "false");
        $solrQuery->addParam("spellcheck.count", "15");
        $solrQuery->addParam("spellcheck.maxCollations", "5");
        $solrQuery->addParam("spellcheck.maxCollationTries", "15");
        $solrQuery->addParam("spellcheck.collate", "true");
        $solrQuery->addParam("spellcheck.collateExtendedResults", "true");
        $solrQuery->addParam("spellcheck.extendedResults", "true");
        $solrQuery->addParam("facet", "true");
        $solrQuery->addParam("facet.sort", "count");
        $solrQuery->addParam("facet.method", "enum");
        $solrQuery->addParam(
            "facet.prefix",
            implode(' ', $query->getTermList())
        );
        $solrQuery->addParam("facet.limit", $query->getLimit());
        $solrQuery->addParam("facet.field", $query->getField());

        $solrQuery->setOmitHeader(false);
        $solrQuery->setStart(0);
        $solrQuery->setRows(0);

        // Filter
        foreach ($query->getFilterList() as $filter) {
            $solrQuery->createFilterQuery($filter->getKey())
                ->setQuery($filter->getQuery())
                ->setTags($filter->getTags());
        }

        return $solrQuery;
    }

    private function buildResult(
        SolrSelectResult $solrResult,
        string $resultField
    ): SuggestResult {
        $suggestions = $this->parseSuggestion(
            $solrResult->getResponse()->getBody(),
            $resultField
        );
        return new SuggestResult($suggestions, $solrResult->getQueryTime());
    }

    /**
     * @throws UnexpectedResultException
     * @return Suggestion[]
     */
    private function parseSuggestion(
        string $responseBody,
        string $facetField
    ): array {
        try {
            $json = json_decode(
                $responseBody,
                true,
                5,
                JSON_THROW_ON_ERROR
            );
            $facets =
                $json['facet_counts']['facet_fields'][$facetField]
                ?? [];

            $len = count($facets);

            $suggestions = [];
            for ($i = 0; $i < $len; $i += 2) {
                $term = $facets[$i];
                $hits = $facets[$i + 1];
                $suggestions[] = new Suggestion($term, $hits);
            }

            return $suggestions;
        } catch (JsonException $e) {
            throw new UnexpectedResultException(
                $responseBody,
                "Invalid JSON for suggest result",
                0,
                $e
            );
        }
    }
}
