<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\Boosting;
use Atoolo\Search\Dto\Search\Query\Facet\AbsoluteDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\CategoryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\RelativeDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\GeoPoint;
use Atoolo\Search\Dto\Search\Query\QueryOperator;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Query\Sort\Criteria;
use Atoolo\Search\Service\Search\SiteKit\DefaultBoosting;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;
use Symfony\Component\HttpFoundation\RequestStack;

class SolrQueryConfigurator
{
    /**
     * @param iterable<SolrQueryModifier> $solrQueryModifierList
     */
    public function __construct(
        private readonly Schema2xFieldMapper $schemaFieldMapper,
        private readonly QueryTemplateResolver $queryTemplateResolver,
        private readonly RequestStack $requestStack,
        private readonly iterable $solrQueryModifierList = [],
    ) {}

    public function configureBasicSettings(SolrSelectQuery $solrQuery, SearchQuery $query): void
    {
        $solrQuery->setStart($query->offset);
        $solrQuery->setRows($query->limit);

        if ($query->spellcheck) {
            $this->addSpellcheck($solrQuery);
        }

        $solrQuery->setOmitHeader(false);

        $this->addUserGroups($solrQuery);

        // applying optional modifiers to search query, e.g. for adding return fields
        foreach ($this->solrQueryModifierList as $solrQueryModifier) {
            $solrQuery = $solrQueryModifier->modify($solrQuery);
        }
    }

    public function addSpellcheck(SolrSelectQuery $solrQuery): void
    {
        $spellcheck = $solrQuery->getSpellcheck();
        $spellcheck->setCollate(true);
        $spellcheck->setExtendedResults(true);
    }

    /**
     * @param Criteria[] $criteriaList
     */
    public function addSortToSolrQuery(
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

    public function addRequiredFieldListToSolrQuery(
        SolrSelectQuery $solrQuery,
        bool $explain,
        bool $expandByDate,
    ): void {
        if ($explain) {
            $solrQuery->addField('explain:[explain style=nl]');
        }
        if ($expandByDate) {
            $solrQuery->addField('[parent]');
            $solrQuery->addField('_nest_path_');
            $solrQuery->addField('_nest_parent_');
            $solrQuery->addField('_root_');
        }
    }

    public function addTextFilterToSolrQuery(
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

    public function escapeTerm(
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

    public function addQueryDefaultOperatorToSolrQuery(
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
    public function addFilterQueriesToSolrQuery(
        SolrSelectQuery $solrQuery,
        array $filterList,
        bool $archive,
        SolrQueryType $queryType,
    ): void {
        $filterAppender = new SolrQueryFilterAppender(
            $solrQuery,
            $this->schemaFieldMapper,
            $this->queryTemplateResolver,
            $queryType,
        );
        foreach ($filterList as $filter) {
            $filterAppender->append($filter);
        }
        if (!$archive) {
            $filterAppender->excludeArchived();
        }
    }

    public function addDistanceField(
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

    public function addTimezone(SolrSelectQuery $solrQuery, ?\DateTimeZone $timeZone): void
    {
        if ($timeZone !== null) {
            $solrQuery->setTimezone($timeZone);
        } elseif (date_default_timezone_get()) {
            $solrQuery->setTimezone(date_default_timezone_get());
        }
    }

    /**
     * @param \Atoolo\Search\Dto\Search\Query\Facet\Facet[] $facetList
     */
    public function addFacetListToSolrQuery(
        SolrSelectQuery $solrQuery,
        array $facetList,
        bool $expandByDate,
    ): void {
        $facetAppender = new SolrQueryFacetAppender(
            $solrQuery,
            $this->schemaFieldMapper,
            $this->queryTemplateResolver,
        );
        foreach ($facetList as $facet) {
            $facetAppender->append($facet);
        }
    }

    /**
     * @param \Atoolo\Search\Dto\Search\Query\Facet\Facet[] $facetList
     */
    public function addParentFacetListToSolrQuery(
        SolrSelectQuery $solrQuery,
        array $facetList,
    ): void {
        $facetAppender = new SolrQueryFacetAppender(
            $solrQuery,
            $this->schemaFieldMapper,
            $this->queryTemplateResolver,
        );
        $filteredFacets = array_filter($facetList, function ($filter) {
            return (
                $filter instanceof AbsoluteDateRangeFacet === false
                && $filter instanceof RelativeDateRangeFacet === false
                && $filter instanceof CategoryFacet === false
            );
        });
        foreach ($filteredFacets as $facet) {
            $facetAppender->append($facet);
        }
    }

    /**
     * @param \Atoolo\Search\Dto\Search\Query\Facet\Facet[] $facetList
     */
    public function addChildFacetListToSolrQuery(
        SolrSelectQuery $solrQuery,
        array $facetList,
        bool $expandByDate,
    ): void {
        $facetAppender = new SolrQueryFacetAppender(
            $solrQuery,
            $this->schemaFieldMapper,
            $this->queryTemplateResolver,
        );
        $filteredFacets = array_filter($facetList, function ($filter) {
            return (
                $filter instanceof AbsoluteDateRangeFacet
                || $filter instanceof RelativeDateRangeFacet
                || $filter instanceof CategoryFacet
            );
        });
        foreach ($filteredFacets as $facet) {
            $facetAppender->append($facet);
        }
    }

    public function addBoosting(
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

    public function addUserGroups(SolrSelectQuery $solrQuery): void
    {
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
}
