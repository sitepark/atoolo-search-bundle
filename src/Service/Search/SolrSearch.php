<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Boosting;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\GeoPoint;
use Atoolo\Search\Dto\Search\Query\QueryOperator;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Query\Sort\Criteria;
use Atoolo\Search\Dto\Search\Result\Facet;
use Atoolo\Search\Dto\Search\Result\FacetGroup;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Dto\Search\Result\Spellcheck;
use Atoolo\Search\Dto\Search\Result\SpellcheckSuggestion;
use Atoolo\Search\Dto\Search\Result\SpellcheckWord;
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
use Symfony\Component\HttpFoundation\RequestStack;

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
        'sp_date_list',
        'sp_meta_*',
    ];

    /**
     * @param iterable<SolrQueryModifier> $solrQueryModifierList
     */
    public function __construct(
        private readonly IndexName $index,
        private readonly SolrClientFactory $clientFactory,
        private readonly SolrResultToResourceResolver $resourceResolver,
        private readonly Schema2xFieldMapper $schemaFieldMapper,
        private readonly RequestStack $requestStack,
        private readonly iterable $solrQueryModifierList = [],
    ) {}

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
        SearchQuery $query,
    ): SolrSelectQuery {

        $solrQuery = $client->createSelect();

        $solrQuery->setStart($query->offset);
        $solrQuery->setRows($query->limit);

        if ($query->spellcheck) {
            $this->addSpellcheck($solrQuery);
        }

        // to get query-time
        $solrQuery->setOmitHeader(false);

        $this->addSortToSolrQuery($solrQuery, $query->sort);
        $this->addRequiredFieldListToSolrQuery($solrQuery, $query->explain);
        $this->addTextFilterToSolrQuery($solrQuery, $query->text);
        $this->addQueryDefaultOperatorToSolrQuery(
            $solrQuery,
            $query->defaultQueryOperator,
        );
        $this->addFilterQueriesToSolrQuery(
            $solrQuery,
            $query->filter,
            $query->archive,
        );
        $this->addFacetListToSolrQuery(
            $solrQuery,
            $query->facets,
        );
        $this->addDistanceField($solrQuery, $query->distanceReferencePoint);

        if ($query->timeZone !== null) {
            $solrQuery->setTimezone($query->timeZone);
        } elseif (date_default_timezone_get()) {
            $solrQuery->setTimezone(date_default_timezone_get());
        }

        $this->addBoosting($solrQuery, $query->boosting);
        $this->addUserGroups($solrQuery);

        // applying optional modifiers to search query, e.g. for adding return fields
        foreach ($this->solrQueryModifierList as $solrQueryModifier) {
            $solrQuery = $solrQueryModifier->modify($solrQuery);
        }

        return $solrQuery;
    }

    private function addSpellcheck(
        SolrSelectQuery $solrQuery,
    ): void {
        $spellcheck = $solrQuery->getSpellcheck();
        $spellcheck->setCollate(true);
        $spellcheck->setExtendedResults(true);
    }

    /**
     * @param Criteria[] $criteriaList
     */
    private function addSortToSolrQuery(
        SolrSelectQuery $solrQuery,
        array $criteriaList,
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
        SolrSelectQuery $solrQuery,
        bool $explain,
    ): void {
        $solrQuery->setFields(
            self::QUERY_FIELDS_REQUIRED,
        );
        if ($explain) {
            $solrQuery->addField('explain:[explain style=nl]');
        }
    }

    private function addTextFilterToSolrQuery(
        SolrSelectQuery $solrQuery,
        string $text,
    ): void {
        if (empty($text)) {
            return;
        }
        $terms = preg_split(
            '/("[^"]*")|\h+/',
            $text,
            -1,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE,
        ) ?: '';
        $that = $this;
        $terms = array_map(
            static function ($term) use ($solrQuery, $that) {
                return $that->escapeTerm($term, $solrQuery);
            },
            is_array($terms) ? $terms : [$terms],
        );
        $text = implode(' ', $terms);
        $solrQuery->setQuery($text);
    }

    private function escapeTerm(
        string $term,
        SolrSelectQuery $solrQuery,
    ): string {
        $term = trim($term);
        $operator = ($term[0] === '+' || $term[0] === '-') ? $term[0] : null;
        if ($operator !== null) {
            $term = substr($term, 1);
        }
        $quoted = $term[0] === '"' && $term[-1] === '"';
        if ($quoted) {
            $term = substr($term, 1, -1);
        }

        $escapedTerm = $solrQuery->getHelper()->escapeTerm($term);
        if ($operator !== null) {
            $escapedTerm = $operator . $escapedTerm;
        }
        if ($quoted) {
            $escapedTerm = '"' . $escapedTerm . '"';
        }
        return $escapedTerm;
    }

    private function addQueryDefaultOperatorToSolrQuery(
        SolrSelectQuery $solrQuery,
        QueryOperator $operator,
    ): void {
        $solrQuery->setQueryDefaultOperator(
            $operator === QueryOperator::OR
                ? SolrSelectQuery::QUERY_OPERATOR_OR
                : SolrSelectQuery::QUERY_OPERATOR_AND,
        );
    }

    /**
     * @param Filter[] $filterList
     */
    private function addFilterQueriesToSolrQuery(
        SolrSelectQuery $solrQuery,
        array $filterList,
        bool $archive,
    ): void {
        $filterAppender = new SolrQueryFilterAppender(
            $solrQuery,
            $this->schemaFieldMapper,
        );
        foreach ($filterList as $filter) {
            $filterAppender->append($filter);
        }
        if (!$archive) {
            $filterAppender->excludeArchived();
        }
    }

    private function addDistanceField(
        SolrSelectQuery $solrQuery,
        ?GeoPoint $distanceReferencePoint,
    ): void {
        if ($distanceReferencePoint === null) {
            return;
        }
        $params = [
            $this->schemaFieldMapper->getGeoPointField(),
            $distanceReferencePoint->lat,
            $distanceReferencePoint->lng,
        ];
        $solrQuery->addField('distance:geodist(' . implode(',', $params) . ')');
    }

    /**
     * @param \Atoolo\Search\Dto\Search\Query\Facet\Facet[] $facetList
     */
    private function addFacetListToSolrQuery(
        SolrSelectQuery $solrQuery,
        array $facetList,
    ): void {
        $facetAppender = new SolrQueryFacetAppender(
            $solrQuery,
            $this->schemaFieldMapper,
        );
        foreach ($facetList as $facet) {
            $facetAppender->append($facet);
        }
    }

    private function addBoosting(
        SolrSelectQuery $solrQuery,
        ?Boosting $boosting,
    ): void {
        $boosting = $boosting ?? new DefaultBoosting();

        $edismax = $solrQuery->getEDisMax();
        if (!empty($boosting->queryFields)) {
            $edismax->setQueryFields(
                implode(' ', $boosting->queryFields),
            );
        }
        if (!empty($boosting->phraseFields)) {
            $edismax->setPhraseFields(
                implode(' ', $boosting->phraseFields),
            );
        }
        foreach ($boosting->boostQueries as $key => $query) {
            $edismax->addBoostQuery([
                'key' => $key,
                'query' => $query,
            ]);
        }
        if (!empty($boosting->boostFunctions)) {
            $edismax->setBoostFunctions(
                implode(' ', $boosting->boostFunctions),
            );
        }
        if ($boosting->tie > 0.0) {
            $edismax->setTie($boosting->tie);
        }
    }

    private function addUserGroups(
        SolrSelectQuery $solrQuery,
    ): void {
        $session = $this->requestStack->getSession();
        if (!$session->getId()) {
            return;
        }

        $groups = $session->get('auth-groups');
        if (empty($groups)) {
            $groups = $_SESSION['auth-groups'] ?? null;
        }

        if (empty($groups)) {
            return;
        }

        $solrQuery->addParam('groups', $groups);
    }

    private function buildResult(
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
     * @return FacetGroup[]
     */
    private function buildFacetGroupList(
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
            if (
                $resultFacet instanceof SolrFacetField
            ) {
                $facetGroupList[] = $this->buildFacetGroupByField(
                    $facet->key,
                    $resultFacet,
                );
            }

            if (
                $resultFacet instanceof SolrFacetQuery
            ) {
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

    private function buildSpellcheck(
        SelectResult $result,
    ): Spellcheck|null {
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
                : $spellcheckResult->getCollation()->getQuery(),
        );
    }
}
