<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Boosting;
use Atoolo\Search\Dto\Search\Query\Facet\AbsoluteDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\CategoryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ObjectTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\RelativeDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Filter\QueryFilter;
use Atoolo\Search\Dto\Search\Query\GeoPoint;
use Atoolo\Search\Dto\Search\Query\QueryOperator;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Query\Sort\Direction;
use Atoolo\Search\Dto\Search\Query\Sort\Name;
use Atoolo\Search\Service\Search\QueryTemplateResolver;
use Atoolo\Search\Service\Search\Schema2xFieldMapper;
use Atoolo\Search\Service\Search\SolrQueryConfigurator;
use Atoolo\Search\Service\Search\SolrQueryModifier;
use Atoolo\Search\Service\Search\SolrQueryType;
use DateTimeZone;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Solarium\Component\EdisMax;
use Solarium\Component\Facet\Field;
use Solarium\Component\Facet\Query as FacetQuery;
use Solarium\Component\FacetSet;
use Solarium\Component\Spellcheck;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

#[CoversClass(SolrQueryConfigurator::class)]
class SolrQueryConfiguratorTest extends TestCase
{
    private SolrQueryConfigurator $configurator;
    private Schema2xFieldMapper&MockObject $fieldMapper;
    private QueryTemplateResolver&MockObject $templateResolver;
    private RequestStack&MockObject $requestStack;
    private SolrSelectQuery&MockObject $solrQuery;
    private SearchQuery $query;

    protected function setUp(): void
    {
        $this->fieldMapper = $this->createMock(Schema2xFieldMapper::class);
        $this->templateResolver = $this->createMock(QueryTemplateResolver::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $facetField = $this->createMock(Field::class);
        $facetQuery = $this->createMock(FacetQuery::class);
        $facetQuery->method('setQuery')->willReturnSelf();
        $facetSet = $this->createStub(FacetSet::class);
        $facetSet->method('createFacetField')->willReturn($facetField);
        $facetSet->method('createFacetQuery')->willReturn($facetQuery);

        $this->solrQuery = $this->createMock(SolrSelectQuery::class);
        $this->solrQuery->method('getFacetSet')->willReturn($facetSet);

        $this->query = new SearchQuery(
            '',
            ResourceLanguage::default(),
            10,
            20,
            [],
            [],
            [],
            true,
            false,
            false,
            QueryOperator::AND,
            null,
            null,
            null,
            false,
        );

        $queryModifier = new class implements SolrQueryModifier {
            public function modify(SolrSelectQuery $query): SolrSelectQuery
            {
                $query->setRows(100);
                return $query;
            }
        };

        $this->configurator = new SolrQueryConfigurator(
            $this->fieldMapper,
            $this->templateResolver,
            $this->requestStack,
            [$queryModifier],
        );
    }

    public function testConfigureBasicSettings(): void
    {
        $this->solrQuery
            ->expects($this->once())
            ->method('setStart')
            ->with(10);

        $this->solrQuery
            ->expects($this->exactly(2))
            ->method('setRows');

        $spellcheck = $this->createMock(Spellcheck::class);
        $this->solrQuery
            ->expects($this->once())
            ->method('getSpellcheck')
            ->willReturn($spellcheck);

        $this->solrQuery
            ->expects($this->once())
            ->method('setOmitHeader')
            ->with(false);

        $this->configurator->configureBasicSettings($this->solrQuery, $this->query);
    }

    public function testAddSpellcheck(): void
    {
        $spellcheck = $this->createMock(Spellcheck::class);

        $this->solrQuery
            ->expects($this->once())
            ->method('getSpellcheck')
            ->willReturn($spellcheck);

        $spellcheck
            ->expects($this->once())
            ->method('setCollate')
            ->with(true);

        $spellcheck
            ->expects($this->once())
            ->method('setExtendedResults')
            ->with(true);

        $this->configurator->addSpellcheck($this->solrQuery);
    }

    public function testAddSortToSolrQuery(): void
    {
        $criteria1 = new Name(Direction::ASC);
        $criteria2 = new Name(Direction::DESC);
        $criteriaList = [$criteria1, $criteria2];

        $callIndex = 0;
        $expectedArgs = [$criteria1, $criteria2];
        $returnValues = ['field1', 'field2'];

        $this->fieldMapper
            ->expects($this->exactly(2))
            ->method('getSortField')
            ->willReturnCallback(function ($criteria) use (&$callIndex, $expectedArgs, $returnValues) {
                $this->assertSame($expectedArgs[$callIndex], $criteria);
                return $returnValues[$callIndex++];
            });

        $this->solrQuery
            ->expects($this->once())
            ->method('setSorts')
            ->with(['field1' => 'asc', 'field2' => 'desc']);

        $this->configurator->addSortToSolrQuery($this->solrQuery, $criteriaList);
    }

    public function testAddRequiredFieldListToSolrQuery(): void
    {
        $expectedFields = [
            'explain:[explain style=nl]',
            '[parent]',
            '_nest_path_',
            '_nest_parent_',
            '_root_',
        ];
        $callIndex = 0;

        $this->solrQuery
            ->expects($this->exactly(5))
            ->method('addField')
            ->willReturnCallback(function (string $field) use (&$callIndex, $expectedFields): SolrSelectQuery {
                $this->assertSame($expectedFields[$callIndex], $field);
                $callIndex++;
                return $this->solrQuery;
            });

        $this->configurator->addRequiredFieldListToSolrQuery($this->solrQuery, true, true);
    }

    public function testAddTextFilterToSolrQuery(): void
    {
        $helper = $this->createMock(\Solarium\Core\Query\Helper::class);
        $helper->expects($this->once())
            ->method('escapeTerm')
            ->with('test')
            ->willReturn('test');

        $this->solrQuery
            ->expects($this->once())
            ->method('getHelper')
            ->willReturn($helper);

        $this->solrQuery
            ->expects($this->once())
            ->method('setQuery')
            ->with('test');

        $this->configurator->addTextFilterToSolrQuery($this->solrQuery, 'test');
    }

    public function testAddTextFilterToSolrQueryEmpty(): void
    {
        $this->solrQuery
            ->expects($this->never())
            ->method('setQuery');

        $this->configurator->addTextFilterToSolrQuery($this->solrQuery, '');
    }

    public function testEscapeTermWithOperator(): void
    {
        $helper = $this->createMock(\Solarium\Core\Query\Helper::class);
        $helper->expects($this->once())
            ->method('escapeTerm')
            ->with('test')
            ->willReturn('test');

        $this->solrQuery
            ->expects($this->once())
            ->method('getHelper')
            ->willReturn($helper);

        $result = $this->configurator->escapeTerm('+test', $this->solrQuery);
        $this->assertEquals('+test', $result);
    }

    public function testEscapeTermWithQuotes(): void
    {
        $helper = $this->createMock(\Solarium\Core\Query\Helper::class);
        $helper->expects($this->once())
            ->method('escapeTerm')
            ->with('test phrase')
            ->willReturn('test phrase');

        $this->solrQuery
            ->expects($this->once())
            ->method('getHelper')
            ->willReturn($helper);

        $result = $this->configurator->escapeTerm('"test phrase"', $this->solrQuery);
        $this->assertEquals('"test phrase"', $result);
    }

    public function testAddQueryDefaultOperatorToSolrQuery(): void
    {
        $this->solrQuery
            ->expects($this->once())
            ->method('setQueryDefaultOperator')
            ->with(SolrSelectQuery::QUERY_OPERATOR_OR);

        $this->configurator->addQueryDefaultOperatorToSolrQuery($this->solrQuery, QueryOperator::OR);
    }

    public function testAddDistanceFieldNull(): void
    {
        $this->solrQuery
            ->expects($this->never())
            ->method('addField');

        $this->configurator->addDistanceField($this->solrQuery, null);
    }

    public function testAddParentFacetListToSolrQuery(): void
    {
        $objectTypeFacet = new ObjectTypeFacet('objectType', []);
        $dateFacet = new AbsoluteDateRangeFacet(
            'date',
            new \DateTime('2023-01-01'),
            new \DateTime('2023-12-31'),
            null,
        );
        $categoryFacet = new CategoryFacet('category', []);
        $relativeDateFacet = new RelativeDateRangeFacet('relDate', new \DateTime('2023-02-02'));

        $facetList = [$objectTypeFacet, $dateFacet, $categoryFacet, $relativeDateFacet];

        $this->fieldMapper->method('getFacetField')->willReturn('sp_objecttype');

        // Only objectTypeFacet should be processed (others filtered out)
        $this->configurator->addParentFacetListToSolrQuery($this->solrQuery, $facetList);

        // We can't easily mock SolrQueryFacetAppender, so this is more of an integration test
        $this->assertTrue(true); // Just ensure no exceptions are thrown
    }

    public function testAddChildFacetListToSolrQuery(): void
    {
        $objectTypeFacet = new ObjectTypeFacet('objectType', [9]);
        $dateFacet = new AbsoluteDateRangeFacet(
            'date',
            new \DateTime('2023-01-01'),
            new \DateTime('2023-12-31'),
            null,
        );
        $categoryFacet = new CategoryFacet('category', []);

        $facetList = [$objectTypeFacet, $dateFacet, $categoryFacet];

        // Only dateFacet and categoryFacet should be processed
        $this->configurator->addChildFacetListToSolrQuery($this->solrQuery, $facetList, true);

        // Integration test - ensure no exceptions
        $this->assertTrue(true);
    }

    public function testAddBoosting(): void
    {
        $boosting = new Boosting(
            queryFields: ['title^2', 'content'],
            phraseFields: ['title^3'],
            boostQueries: ['boost1' => 'type:important'],
            boostFunctions: ['recip(ms(NOW,date),3.16e-11,1,1)'],
            tie: 0.1,
        );

        $edismax = $this->createMock(EdisMax::class);
        $this->solrQuery
            ->expects($this->once())
            ->method('getEDisMax')
            ->willReturn($edismax);

        $edismax
            ->expects($this->once())
            ->method('setQueryFields')
            ->with('title^2 content');

        $edismax
            ->expects($this->once())
            ->method('setPhraseFields')
            ->with('title^3');

        $edismax
            ->expects($this->exactly(1))
            ->method('addBoostQuery')
            ->with(['key' => 'boost1', 'query' => 'type:important']);

        $edismax
            ->expects($this->once())
            ->method('setBoostFunctions')
            ->with('recip(ms(NOW,date),3.16e-11,1,1)');

        $edismax
            ->expects($this->once())
            ->method('setTie')
            ->with(0.1);

        $this->configurator->addBoosting($this->solrQuery, $boosting);
    }

    public function testAddBoostingNull(): void
    {
        $edismax = $this->createMock(EdisMax::class);
        $this->solrQuery
            ->expects($this->once())
            ->method('getEDisMax')
            ->willReturn($edismax);

        // Should use DefaultBoosting when null is passed
        $this->configurator->addBoosting($this->solrQuery, null);

        $this->assertTrue(true); // Ensure no exceptions
    }

    public function testAddFilterQueriesToSolrQueryWithArchive(): void
    {
        $filter = new QueryFilter('type:article', 'myfilter');

        $filterQuery = $this->createMock(FilterQuery::class);
        $filterQuery
            ->expects($this->once())
            ->method('setQuery')
            ->with('type:article');

        $this->solrQuery
            ->expects($this->once())
            ->method('createFilterQuery')
            ->with('myfilter')
            ->willReturn($filterQuery);

        $this->configurator->addFilterQueriesToSolrQuery(
            $this->solrQuery,
            [$filter],
            true,
            SolrQueryType::QUERY_TYPE_DEFAULT,
        );
    }

    public function testAddFilterQueriesToSolrQueryExcludesArchived(): void
    {
        $filter = new QueryFilter('type:article', 'myfilter');

        $filterQuery = $this->createMock(FilterQuery::class);

        $this->fieldMapper
            ->expects($this->once())
            ->method('getArchiveField')
            ->willReturn('sp_archived');

        $this->solrQuery
            ->expects($this->exactly(2))
            ->method('createFilterQuery')
            ->willReturn($filterQuery);

        $this->configurator->addFilterQueriesToSolrQuery(
            $this->solrQuery,
            [$filter],
            false,
            SolrQueryType::QUERY_TYPE_DEFAULT,
        );
    }

    public function testAddDistanceField(): void
    {
        $geoPoint = new GeoPoint(9.5, 48.5);

        $this->fieldMapper
            ->expects($this->once())
            ->method('getGeoPointField')
            ->willReturn('geo_point');

        $this->solrQuery
            ->expects($this->once())
            ->method('addField')
            ->with('distance:geodist(geo_point,48.5,9.5)');

        $this->configurator->addDistanceField($this->solrQuery, $geoPoint);
    }

    public function testAddTimezone(): void
    {
        $timeZone = new DateTimeZone('Europe/Berlin');

        $this->solrQuery
            ->expects($this->once())
            ->method('setTimezone')
            ->with($timeZone);

        $this->configurator->addTimezone($this->solrQuery, $timeZone);
    }

    public function testAddTimezoneWithNull(): void
    {
        $this->solrQuery
            ->expects($this->once())
            ->method('setTimezone')
            ->with(date_default_timezone_get());

        $this->configurator->addTimezone($this->solrQuery, null);
    }

    public function testAddFacetListToSolrQuery(): void
    {
        $objectTypeFacet = new ObjectTypeFacet('objectType', []);
        $this->fieldMapper->method('getFacetField')->willReturn('sp_objecttype');

        $this->configurator->addFacetListToSolrQuery($this->solrQuery, [$objectTypeFacet], false);

        $this->assertTrue(true);
    }

    public function testAddUserGroupsNoSessionId(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('getId')->willReturn('');
        $this->requestStack->method('getSession')->willReturn($session);

        $this->solrQuery
            ->expects($this->never())
            ->method('addParam');

        $query = new SearchQuery(
            '',
            ResourceLanguage::default(),
            0,
            10,
            [],
            [],
            [],
            false,
            false,
            false,
            QueryOperator::AND,
            null,
            null,
            null,
        );
        $this->configurator->configureBasicSettings($this->solrQuery, $query);
    }

    public function testAddUserGroupsNoGroups(): void
    {
        $session = $this->createMock(Session::class);
        $session->method('getId')->willReturn('session-id');
        $session->method('get')->with('auth-groups')->willReturn(null);
        $this->requestStack->method('getSession')->willReturn($session);

        $this->solrQuery
            ->expects($this->never())
            ->method('addParam');

        $query = new SearchQuery(
            '',
            ResourceLanguage::default(),
            0,
            10,
            [],
            [],
            [],
            false,
            false,
            false,
            QueryOperator::AND,
            null,
            null,
            null,
        );
        $this->configurator->configureBasicSettings($this->solrQuery, $query);
    }

    public function testAddUserGroupsWithGroups(): void
    {
        $groups = ['group1', 'group2'];
        $session = $this->createMock(Session::class);
        $session->method('getId')->willReturn('session-id');
        $session->method('get')->with('auth-groups')->willReturn($groups);
        $this->requestStack->method('getSession')->willReturn($session);

        $this->solrQuery
            ->expects($this->once())
            ->method('addParam')
            ->with('groups', $groups);

        $query = new SearchQuery(
            '',
            ResourceLanguage::default(),
            0,
            10,
            [],
            [],
            [],
            false,
            false,
            false,
            QueryOperator::AND,
            null,
            null,
            null,
        );
        $this->configurator->configureBasicSettings($this->solrQuery, $query);
    }
}
