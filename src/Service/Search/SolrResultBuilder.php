<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Result\Facet;
use Atoolo\Search\Dto\Search\Result\FacetGroup;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Dto\Search\Result\Spellcheck;
use Atoolo\Search\Dto\Search\Result\SpellcheckSuggestion;
use Atoolo\Search\Dto\Search\Result\SpellcheckWord;
use InvalidArgumentException;
use Solarium\Component\Result\Facet\Field as SolrFacetField;
use Solarium\Component\Result\Facet\Query as SolrFacetQuery;
use Solarium\QueryType\Select\Result\Result as SelectResult;

class SolrResultBuilder
{
    public function __construct(
        private readonly SolrResultToResourceResolver $resourceResolver,
    ) {}

    public function buildResult(
        SearchQuery $query,
        SelectResult $result,
        ResourceLanguage $lang,
    ): SearchResult {
        $resourceList = $this->resourceResolver->loadResourceList($result, $lang);
        $facetGroupList = $this->buildFacetGroupList($query, $result);
        $spellcheck = $this->buildSpellcheck($result);

        return new SearchResult(
            total: $result->getNumFound() ?? 0,
            limit: $query->limit,
            offset: $query->offset,
            results: $resourceList,
            facetGroups: $facetGroupList,
            spellcheck: $spellcheck,
            queryTime: $result->getQueryTime() ?? 0,
        );
    }

    /**
     * @param FacetGroup[] $parentFacetGroups
     * @param FacetGroup[] $childFacetGroups
     */
    public function buildExpandedResult(
        SearchQuery $query,
        SelectResult $parentResult,
        SelectResult $childResult,
        array $parentFacetGroups,
        array $childFacetGroups,
        ResourceLanguage $lang,
    ): SearchResult {
        $childSearchResult = $this->buildResult($query, $childResult, $lang);
        $parentSpellcheck = $this->buildSpellcheck($parentResult);

        return new SearchResult(
            total: $childResult->getNumFound() ?? 0,
            limit: $query->limit,
            offset: $query->offset,
            results: $childSearchResult->results,
            facetGroups: array_merge($parentFacetGroups, $childFacetGroups),
            spellcheck: $parentSpellcheck,
            queryTime: ($parentResult->getQueryTime() + $childResult->getQueryTime()),
        );
    }

    /**
     * @return FacetGroup[]
     */
    public function buildFacetGroupList(
        SearchQuery $query,
        SelectResult $result,
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
            if ($resultFacet instanceof SolrFacetField) {
                $facetGroupList[] = $this->buildFacetGroupByField(
                    $facet->key,
                    $resultFacet,
                );
            }

            if ($resultFacet instanceof SolrFacetQuery) {
                $facetGroupList[] = $this->buildFacetGroupByQuery(
                    $facet->key,
                    $resultFacet,
                );
            }
        }
        return $facetGroupList;
    }

    private function buildFacetGroupByField(
        string $key,
        SolrFacetField $solrFacet,
    ): FacetGroup {
        $facetList = [];
        foreach ($solrFacet as $value => $count) {
            if (!is_int($count)) {
                throw new InvalidArgumentException(
                    'facet count should be a int: ' . $count,
                );
            }
            $facetList[] = new Facet((string) $value, $count);
        }
        return new FacetGroup($key, $facetList);
    }

    private function buildFacetGroupByQuery(
        string $key,
        SolrFacetQuery $solrFacet,
    ): FacetGroup {
        $facetList = [];

        $value = $solrFacet->getValue();
        $value = is_int($value) ? $value : 0;

        $facetList[] = new Facet($key, $value);
        return new FacetGroup($key, $facetList);
    }

    public function buildSpellcheck(
        SelectResult $result,
    ): ?Spellcheck {
        $spellcheckResult = $result->getSpellcheck();
        if ($spellcheckResult === null) {
            return null;
        }

        if ($spellcheckResult->getCorrectlySpelled()) {
            return null;
        }

        $suggestions = [];
        foreach ($spellcheckResult->getSuggestions() as $suggestion) {
            $original = new SpellcheckWord(
                $suggestion->getOriginalTerm() ?? '',
                $suggestion->getOriginalFrequency() ?? 0,
            );
            $suggestion = new SpellcheckWord(
                $suggestion->getWord() ?? '',
                $suggestion->getFrequency(),
            );
            $suggestions[] = new SpellcheckSuggestion(
                $original,
                $suggestion,
            );
        }
        return new Spellcheck(
            $suggestions,
            $spellcheckResult->getCollation() === null
                ? ''
                : str_replace('\\', '', $spellcheckResult->getCollation()->getQuery()),
        );
    }
}
