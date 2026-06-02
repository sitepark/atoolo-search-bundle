<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Service\Search\SolrQueryBuilder;
use Atoolo\Search\Service\Search\SolrQueryConfigurator;
use Atoolo\Search\Service\Search\SolrQueryType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Solarium\Client;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;

#[CoversClass(SolrQueryBuilder::class)]
class SolrQueryBuilderTest extends TestCase
{
    private SolrQueryBuilder $builder;
    private SolrQueryConfigurator&MockObject $configurator;
    private Client&MockObject $client;
    private SolrSelectQuery&MockObject $solrQuery;

    protected function setUp(): void
    {
        $this->configurator = $this->createMock(SolrQueryConfigurator::class);
        $this->client = $this->createMock(Client::class);
        $this->solrQuery = $this->createMock(SolrSelectQuery::class);

        $this->builder = new SolrQueryBuilder($this->configurator);
    }

    public function testBuildDefaultQuery(): void
    {
        $query = new SearchQuery(
            text: 'test search',
            facets: [],
            filter: [],
            sort: [],
            limit: 10,
            offset: 0,
            lang: ResourceLanguage::default(),
            archive: false,
            spellcheck: false,
            explain: false,
            expandByDate: false,
            distanceReferencePoint: null,
            defaultQueryOperator: \Atoolo\Search\Dto\Search\Query\QueryOperator::AND,
            boosting: null,
            timeZone: null,
        );

        $this->client
            ->expects($this->once())
            ->method('createSelect')
            ->willReturn($this->solrQuery);

        $this->configurator
            ->expects($this->once())
            ->method('configureBasicSettings')
            ->with($this->solrQuery, $query);

        $this->configurator
            ->expects($this->once())
            ->method('addSortToSolrQuery')
            ->with($this->solrQuery, $query->sort);

        $this->configurator
            ->expects($this->once())
            ->method('addRequiredFieldListToSolrQuery')
            ->with($this->solrQuery, false, false);

        $this->configurator
            ->expects($this->once())
            ->method('addTextFilterToSolrQuery')
            ->with($this->solrQuery, 'test search');

        $this->configurator
            ->expects($this->once())
            ->method('addQueryDefaultOperatorToSolrQuery')
            ->with($this->solrQuery, $query->defaultQueryOperator);

        $this->configurator
            ->expects($this->once())
            ->method('addFilterQueriesToSolrQuery')
            ->with(
                $this->solrQuery,
                [],
                false,
                SolrQueryType::QUERY_TYPE_DEFAULT,
            );

        $this->configurator
            ->expects($this->once())
            ->method('addFacetListToSolrQuery')
            ->with($this->solrQuery, [], false);

        $this->configurator
            ->expects($this->once())
            ->method('addDistanceField')
            ->with($this->solrQuery, null);

        $this->configurator
            ->expects($this->once())
            ->method('addTimezone')
            ->with($this->solrQuery, null);

        $this->configurator
            ->expects($this->once())
            ->method('addBoosting')
            ->with($this->solrQuery, null);

        $result = $this->builder->buildDefaultQuery($this->client, $query);

        $this->assertSame($this->solrQuery, $result);
    }

    public function testBuildDateParentQuery(): void
    {
        $query = new SearchQuery(
            text: 'test search',
            facets: [],
            filter: [],
            sort: [],
            limit: 10,
            offset: 0,
            lang: ResourceLanguage::default(),
            archive: false,
            spellcheck: true,
            explain: false,
            expandByDate: true,
            distanceReferencePoint: null,
            defaultQueryOperator: \Atoolo\Search\Dto\Search\Query\QueryOperator::AND,
            boosting: null,
            timeZone: null,
        );

        $filterQuery = $this->createMock(\Solarium\QueryType\Select\Query\FilterQuery::class);

        $this->client
            ->expects($this->once())
            ->method('createSelect')
            ->willReturn($this->solrQuery);

        $this->solrQuery
            ->expects($this->once())
            ->method('createFilterQuery')
            ->with(SolrQueryType::QUERY_TYPE_PARENT->value)
            ->willReturn($filterQuery);

        $filterQuery
            ->expects($this->once())
            ->method('setQuery')
            ->with("{!parent which='*:* -_nest_parent_:*' filters=\$" . SolrQueryType::QUERY_TYPE_PARENT->value . "}");

        $this->solrQuery
            ->expects($this->once())
            ->method('setStart')
            ->with(0);

        $this->solrQuery
            ->expects($this->once())
            ->method('setRows')
            ->with(0);

        $this->solrQuery
            ->expects($this->once())
            ->method('setOmitHeader')
            ->with(false);

        $this->solrQuery
            ->expects($this->once())
            ->method('setFields')
            ->with(\Atoolo\Search\Service\Search\SolrSearch::QUERY_FIELDS_REQUIRED);

        $this->configurator
            ->expects($this->once())
            ->method('addSpellcheck')
            ->with($this->solrQuery);

        $this->configurator
            ->expects($this->once())
            ->method('addTextFilterToSolrQuery')
            ->with($this->solrQuery, 'test search');

        $this->configurator
            ->expects($this->once())
            ->method('addQueryDefaultOperatorToSolrQuery')
            ->with($this->solrQuery, $query->defaultQueryOperator);

        $this->configurator
            ->expects($this->once())
            ->method('addFilterQueriesToSolrQuery')
            ->with(
                $this->solrQuery,
                [],
                false,
                SolrQueryType::QUERY_TYPE_PARENT,
            );

        $this->configurator
            ->expects($this->once())
            ->method('addParentFacetListToSolrQuery')
            ->with($this->solrQuery, []);

        $this->configurator
            ->expects($this->once())
            ->method('addDistanceField')
            ->with($this->solrQuery, null);

        $this->configurator
            ->expects($this->once())
            ->method('addTimezone')
            ->with($this->solrQuery, null);

        $this->configurator
            ->expects($this->once())
            ->method('addBoosting')
            ->with($this->solrQuery, null);

        $result = $this->builder->buildDateParentQuery($this->client, $query);

        $this->assertSame($this->solrQuery, $result);
    }

    public function testBuildDateChildQuery(): void
    {
        $query = new SearchQuery(
            text: 'test search',
            facets: [],
            filter: [],
            sort: [],
            limit: 10,
            offset: 5,
            lang: ResourceLanguage::default(),
            archive: false,
            spellcheck: false,
            explain: true,
            expandByDate: true,
            distanceReferencePoint: null,
            defaultQueryOperator: \Atoolo\Search\Dto\Search\Query\QueryOperator::OR,
            boosting: null,
            timeZone: null,
        );

        $filterQuery = $this->createMock(\Solarium\QueryType\Select\Query\FilterQuery::class);

        $this->client
            ->expects($this->once())
            ->method('createSelect')
            ->willReturn($this->solrQuery);

        $this->solrQuery
            ->expects($this->once())
            ->method('createFilterQuery')
            ->with(SolrQueryType::QUERY_TYPE_CHILD->value)
            ->willReturn($filterQuery);

        $this->solrQuery
            ->expects($this->once())
            ->method('setStart')
            ->with(5);

        $this->solrQuery
            ->expects($this->once())
            ->method('setRows')
            ->with(10);

        $this->solrQuery
            ->expects($this->once())
            ->method('setOmitHeader')
            ->with(false);

        $this->configurator
            ->expects($this->once())
            ->method('addSortToSolrQuery')
            ->with($this->solrQuery, $query->sort);

        $this->configurator
            ->expects($this->once())
            ->method('addRequiredFieldListToSolrQuery')
            ->with($this->solrQuery, true, true);

        $this->configurator
            ->expects($this->once())
            ->method('addQueryDefaultOperatorToSolrQuery')
            ->with($this->solrQuery, $query->defaultQueryOperator);

        $this->configurator
            ->expects($this->once())
            ->method('addFilterQueriesToSolrQuery')
            ->with(
                $this->solrQuery,
                [],
                false,
                SolrQueryType::QUERY_TYPE_CHILD,
            );

        $this->configurator
            ->expects($this->once())
            ->method('addChildFacetListToSolrQuery')
            ->with($this->solrQuery, [], true);

        $this->configurator
            ->expects($this->once())
            ->method('addTimezone')
            ->with($this->solrQuery, null);

        $result = $this->builder->buildDateChildQuery($this->client, $query);

        $this->assertSame($this->solrQuery, $result);
    }

    public function testBuildDateChildQueryWithTextAndBoosting(): void
    {
        $boosting = new \Atoolo\Search\Service\Search\SiteKit\DefaultBoosting();
        $query = new SearchQuery(
            text: 'search text',
            facets: [],
            filter: [],
            sort: [],
            limit: 10,
            offset: 0,
            lang: ResourceLanguage::default(),
            archive: false,
            spellcheck: false,
            explain: false,
            expandByDate: true,
            distanceReferencePoint: null,
            defaultQueryOperator: \Atoolo\Search\Dto\Search\Query\QueryOperator::AND,
            boosting: $boosting,
            timeZone: null,
        );

        $filterQuery = $this->createMock(\Solarium\QueryType\Select\Query\FilterQuery::class);

        $this->client
            ->expects($this->once())
            ->method('createSelect')
            ->willReturn($this->solrQuery);

        $this->solrQuery
            ->expects($this->once())
            ->method('createFilterQuery')
            ->with(SolrQueryType::QUERY_TYPE_CHILD->value)
            ->willReturn($filterQuery);

        $expectedQueryString = '{!child of=\'*:* -_nest_parent_:*\' filters=$' . SolrQueryType::QUERY_TYPE_CHILD->value . '}'
                              . '{!edismax qf=\'' . implode(' ', $boosting->queryFields) . '\'}(search text)';

        $filterQuery
            ->expects($this->once())
            ->method('setQuery')
            ->with($expectedQueryString);

        $result = $this->builder->buildDateChildQuery($this->client, $query);

        $this->assertSame($this->solrQuery, $result);
    }
}
