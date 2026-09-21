<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Service\Search\SiteKit\DefaultBoosting;
use Solarium\Core\Client\Client;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;

class SolrQueryBuilder
{
    public function __construct(
        private readonly SolrQueryConfigurator $configurator,
    ) {}

    public function buildDefaultQuery(
        Client $client,
        SearchQuery $query,
    ): SolrSelectQuery {
        $solrQuery = $client->createSelect();

        $this->configurator->configureBasicSettings($solrQuery, $query);
        $this->configurator->addSortToSolrQuery($solrQuery, $query->sort);
        $this->configurator->addRequiredFieldListToSolrQuery($solrQuery, $query->explain, $query->expandByDate);
        $this->configurator->addTextFilterToSolrQuery($solrQuery, $query->text);
        $this->configurator->addQueryDefaultOperatorToSolrQuery($solrQuery, $query->defaultQueryOperator);
        $this->configurator->addFilterQueriesToSolrQuery(
            $solrQuery,
            $query->filter,
            $query->archive,
            SolrQueryType::QUERY_TYPE_DEFAULT,
        );
        $this->configurator->addFacetListToSolrQuery($solrQuery, $query->facets, $query->expandByDate);
        $this->configurator->addDistanceField($solrQuery, $query->distanceReferencePoint);
        $this->configurator->addTimezone($solrQuery, $query->timeZone);
        $this->configurator->addBoosting($solrQuery, $query->boosting);

        return $solrQuery;
    }

    public function buildDateParentQuery(
        Client $client,
        SearchQuery $query,
    ): SolrSelectQuery {
        $solrQuery = $client->createSelect();

        $parentFilterQuery = $solrQuery->createFilterQuery(SolrQueryType::QUERY_TYPE_PARENT->value);
        $parentFilterQuery->setQuery(
            "{!parent which='*:* -_nest_parent_:*' filters=\$" . SolrQueryType::QUERY_TYPE_PARENT->value . "}",
        );

        $solrQuery->setStart(0);
        $solrQuery->setRows(0);
        if ($query->spellcheck) {
            $this->configurator->addSpellcheck($solrQuery);
        }
        $solrQuery->setOmitHeader(false);
        $solrQuery->setFields(SolrSearch::QUERY_FIELDS_REQUIRED);

        $this->configurator->addTextFilterToSolrQuery($solrQuery, $query->text);
        $this->configurator->addQueryDefaultOperatorToSolrQuery($solrQuery, $query->defaultQueryOperator);
        // set the relevant child filter.
        $this->configurator->addFilterQueriesToSolrQuery(
            $solrQuery,
            $query->filter,
            $query->archive,
            SolrQueryType::QUERY_TYPE_PARENT,
        );
        // here the relevant parent facets.
        $this->configurator->addParentFacetListToSolrQuery($solrQuery, $query->facets);
        $this->configurator->addDistanceField($solrQuery, $query->distanceReferencePoint);
        $this->configurator->addTimezone($solrQuery, $query->timeZone);
        $this->configurator->addBoosting($solrQuery, $query->boosting);
        $this->configurator->addUserGroups($solrQuery);
        return $solrQuery;
    }

    public function buildDateChildQuery(
        Client $client,
        SearchQuery $query,
    ): SolrSelectQuery {
        $solrQuery = $client->createSelect();

        $parentFilterQuery = $solrQuery->createFilterQuery(SolrQueryType::QUERY_TYPE_CHILD->value);
        $parentFilterQueryString
            = '{!child of=\'*:* -_nest_parent_:*\' filters=$' . SolrQueryType::QUERY_TYPE_CHILD->value . '}';
        if (!empty($query->text)) {
            $boosting = $query->boosting ?? new DefaultBoosting();
            $parentFilterQueryString
                .= '{!edismax qf=\'' . implode(' ', $boosting->queryFields) . '\'}(' . $query->text . ')';
        }
        $parentFilterQuery->setQuery($parentFilterQueryString);

        $solrQuery->setStart($query->offset);
        $solrQuery->setRows($query->limit);
        $solrQuery->setOmitHeader(false);
        $this->configurator->addSortToSolrQuery($solrQuery, $query->sort);
        $this->configurator->addRequiredFieldListToSolrQuery($solrQuery, $query->explain, $query->expandByDate);
        $this->configurator->addQueryDefaultOperatorToSolrQuery($solrQuery, $query->defaultQueryOperator);

        $this->configurator->addFilterQueriesToSolrQuery(
            $solrQuery,
            $query->filter,
            $query->archive,
            SolrQueryType::QUERY_TYPE_CHILD,
        );
        $this->configurator->addChildFacetListToSolrQuery($solrQuery, $query->facets, $query->expandByDate);
        $this->configurator->addTimezone($solrQuery, $query->timeZone);

        return $solrQuery;
    }
}
