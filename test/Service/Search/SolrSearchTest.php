<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Facet\MultiQueryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ObjectTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\QueryFacet;
use Atoolo\Search\Dto\Search\Query\Filter\ObjectTypeFilter;
use Atoolo\Search\Dto\Search\Query\GeoPoint;
use Atoolo\Search\Dto\Search\Query\QueryOperator;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Query\Sort\Date;
use Atoolo\Search\Dto\Search\Query\Sort\Name;
use Atoolo\Search\Dto\Search\Query\Sort\Natural;
use Atoolo\Search\Dto\Search\Query\Sort\Score;
use Atoolo\Search\Dto\Search\Result\FacetGroup;
use Atoolo\Search\Dto\Search\Result\Spellcheck;
use Atoolo\Search\Dto\Search\Result\SpellcheckSuggestion;
use Atoolo\Search\Dto\Search\Result\SpellcheckWord;
use Atoolo\Search\Service\IndexName;
use Atoolo\Search\Service\Search\QueryTemplateResolver;
use Atoolo\Search\Service\Search\Schema2xFieldMapper;
use Atoolo\Search\Service\Search\SolrQueryModifier;
use Atoolo\Search\Service\Search\SolrResultToResourceResolver;
use Atoolo\Search\Service\Search\SolrSearch;
use Atoolo\Search\Service\SolrClientFactory;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\Stub;
use PHPUnit\Framework\TestCase;
use Solarium\Client;
use Solarium\Component\FacetSet;
use Solarium\Component\Result\Facet\Field as SolrFacetField;
use Solarium\Component\Result\Facet\Query as SolrFacetQuery;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;
use Solarium\QueryType\Select\Result\Result as SelectResult;
use Solarium\Component\Result\Spellcheck\Result as SpellcheckResult;
use Solarium\Component\Result\Spellcheck\Suggestion as SolrSpellcheckSuggestion;
use Solarium\Component\Result\Spellcheck\Collation as SolrSpellcheckCollation;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

#[CoversClass(SolrSearch::class)]
class SolrSearchTest extends TestCase
{
    private Resource|Stub $resource;

    private SelectResult|Stub $result;

    private SolrSelectQuery&MockObject $solrQuery;

    private SolrSearch $searcher;

    private RequestStack|Stub $requestStack;

    protected function setUp(): void
    {
        $indexName = $this->createStub(IndexName::class);
        $clientFactory = $this->createStub(
            SolrClientFactory::class,
        );
        $client = $this->createStub(Client::class);
        $clientFactory->method('create')->willReturn($client);

        $this->solrQuery = $this->createMock(SolrSelectQuery::class);

        $this->solrQuery->method('createFilterQuery')
            ->willReturn(new FilterQuery());
        $this->solrQuery->method('getFacetSet')
            ->willReturn(new FacetSet());

        $client->method('createSelect')->willReturn($this->solrQuery);

        $this->result = $this->createStub(SelectResult::class);
        $client->method('execute')->willReturn($this->result);

        $this->resource = $this->createStub(Resource::class);

        $resultToResourceResolver = $this->createStub(
            SolrResultToResourceResolver::class,
        );

        $solrQueryModifier = $this->createStub(SolrQueryModifier::class);
        $solrQueryModifier->method('modify')->willReturn($this->solrQuery);

        $resultToResourceResolver
            ->method('loadResourceList')
            ->willReturn([$this->resource]);

        $schemaFieldMapper = $this->createStub(
            Schema2xFieldMapper::class,
        );
        $schemaFieldMapper->method('getGeoPointField')->willReturn('geo_points');

        $queryTemplateResolver = $this->createStub(QueryTemplateResolver::class);

        $this->requestStack = $this->createStub(RequestStack::class);

        $this->searcher = new SolrSearch(
            $indexName,
            $clientFactory,
            $resultToResourceResolver,
            $schemaFieldMapper,
            $queryTemplateResolver,
            $this->requestStack,
            [$solrQueryModifier],
        );
    }

    public function testSelectEmpty(): void
    {
        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 1,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEquals(
            [$this->resource],
            $searchResult->results,
            'unexpected results',
        );
    }

    public function testSelectWithText(): void
    {
        $query = new SearchQuery(
            text: 'cat -dog +bird "mickey mouse"',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEquals(
            [$this->resource],
            $searchResult->results,
            'unexpected results',
        );
    }

    public function testSelectWithSession(): void
    {
        $query = new SearchQuery(
            text: 'abc"',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $session = $this->createStub(Session::class);
        $this->requestStack->method('getSession')
            ->willReturn($session);

        $session->method('getId')->willReturn('123');
        $this->searcher->search($query);

        $this->solrQuery->expects($this->never())
            ->method('addParam');
    }

    public function testSelectWithAuthGroup(): void
    {
        $query = new SearchQuery(
            text: 'abc"',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $session = $this->createStub(Session::class);
        $this->requestStack->method('getSession')
            ->willReturn($session);

        $session->method('getId')->willReturn('123');
        $session->method('get')->willReturn('345');

        $this->solrQuery->expects($this->once())
            ->method('addParam')
            ->with('groups', '345');

        $this->searcher->search($query);
    }

    public function testSelectWithSort(): void
    {
        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [
                new Name(),
                new Date(),
                new Natural(),
                new Score(),
            ],
            filter: [],
            facets: [],
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEquals(
            [$this->resource],
            $searchResult->results,
            'unexpected results',
        );
    }

    public function testSelectWithDistanceField(): void
    {
        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: new GeoPoint(3, 4),
        );

        $this->solrQuery->expects($this->once())
            ->method('addField')
            ->with('distance:geodist(geo_points,4,3)');

        $this->searcher->search($query);
    }

    public function testSelectWithAndDefaultOperator(): void
    {
        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::AND,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEquals(
            [$this->resource],
            $searchResult->results,
            'unexpected results',
        );
    }

    public function testSelectWithFilter(): void
    {
        $filter = new ObjectTypeFilter(['test']);

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [$filter],
            facets: [],
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEquals(
            [$this->resource],
            $searchResult->results,
            'unexpected results',
        );
    }

    public function testSelectWithFacets(): void
    {

        $facets = [
            new ObjectTypeFacet('objectType', ['content'], ['ob']),
            new QueryFacet('query', 'sp_id:123', ['ob']),
            new MultiQueryFacet(
                'multiquery',
                [new QueryFacet('query', 'sp_id:123')],
                ['ob'],
            ),
        ];

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: $facets,
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEquals(
            [$this->resource],
            $searchResult->results,
            'unexpected results',
        );
    }

    public function testSelectWithInvalidFacets(): void
    {

        $facets = [
            $this->createStub(Facet::class),
        ];

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: $facets,
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->searcher->search($query);
    }

    public function testResulWithFacetField(): void
    {

        $facet = new SolrFacetField([
            'content' => 10,
            'media' => 5,
        ]);
        $facetSet = new \Solarium\Component\Result\FacetSet([
            'objectType' => $facet,
        ]);

        $this->result->method('getFacetSet')
            ->willReturn($facetSet);

        $facets = [
            new ObjectTypeFacet('objectType', ['content'], ['ob']),
        ];

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: $facets,
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $searchResult = $this->searcher->search($query);

        $expected = new FacetGroup(
            'objectType',
            [
                new \Atoolo\Search\Dto\Search\Result\Facet('content', 10),
                new \Atoolo\Search\Dto\Search\Result\Facet('media', 5),
            ],
        );

        $this->assertEquals(
            $expected,
            $searchResult->facetGroups[0],
            'unexpected facet results',
        );
    }

    public function testResultWithFacetQuery(): void
    {

        $facet = new SolrFacetQuery(5);
        $facetSet = new \Solarium\Component\Result\FacetSet([
            'aquery' => $facet,
        ]);

        $this->result->method('getFacetSet')
            ->willReturn($facetSet);

        $facets = [
            new QueryFacet('aquery', 'sp_id:123'),
        ];

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: $facets,
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $searchResult = $this->searcher->search($query);

        $expected = new FacetGroup(
            'aquery',
            [
                new \Atoolo\Search\Dto\Search\Result\Facet('aquery', 5),
            ],
        );

        $this->assertEquals(
            $expected,
            $searchResult->facetGroups[0],
            'unexpected facet results',
        );
    }
    public function testInvalidResultFacets(): void
    {

        $facet = new SolrFacetField([
            'content' => 'nonint',
        ]);
        $facetSet = new \Solarium\Component\Result\FacetSet([
            'objectType' => $facet,
        ]);

        $this->result->method('getFacetSet')
            ->willReturn($facetSet);

        $facets = [
            new ObjectTypeFacet('objectType', ['content'], ['ob']),
            new QueryFacet('query', 'sp_id:123', ['ob']),
            new MultiQueryFacet(
                'multiquery',
                [new QueryFacet('query', 'sp_id:123')],
                ['ob'],
            ),
        ];

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: $facets,
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $this->expectException(InvalidArgumentException::class);
        $this->searcher->search($query);
    }

    public function testResultWithoutFacets(): void
    {

        $facetSet = new \Solarium\Component\Result\FacetSet([
        ]);

        $this->result->method('getFacetSet')
            ->willReturn($facetSet);

        $facets = [
            new ObjectTypeFacet('objectType', ['content'], ['ob']),
        ];

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: $facets,
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEmpty(
            $searchResult->facetGroups,
            'facets should be empty',
        );
    }

    /**
     * @throws Exception
     */
    public function testSelectWithSpellcheck(): void
    {

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: true,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $spellcheckResult = $this->createStub(SpellcheckResult::class);
        $spellcheckResult->method('getCorrectlySpelled')->willReturn(false);

        $suggest = $this->createStub(SolrSpellcheckSuggestion::class);
        $suggest->method('getOriginalTerm')->willReturn('abc');
        $suggest->method('getOriginalFrequency')->willReturn(3);
        $suggest->method('getWord')->willReturn('cde');
        $suggest->method('getFrequency')->willReturn(12);

        $spellcheckResult->method('getSuggestions')
            ->willReturn([$suggest]);

        $collection = $this->createStub(SolrSpellcheckCollation::class);
        $collection->method('getQuery')->willReturn('cde');
        $spellcheckResult->method('getCollation')
            ->willReturn($collection);

        $this->result->method('getSpellcheck')
            ->willReturn($spellcheckResult);

        $this->solrQuery->expects($this->once())
            ->method('getSpellcheck');

        $searchResult = $this->searcher->search($query);

        $expected = new Spellcheck(
            [new SpellcheckSuggestion(
                original: new SpellcheckWord('abc', 3),
                suggestion: new SpellcheckWord('cde', 12),
            )],
            'cde',
        );

        $this->assertEquals(
            $expected,
            $searchResult->spellcheck,
            'unexpected spellcheck results',
        );
    }

    /**
     * @throws Exception
     */
    public function testSelectWithCorrectlySpellcheck(): void
    {

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: true,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $spellcheckResult = $this->createStub(SpellcheckResult::class);
        $spellcheckResult->method('getCorrectlySpelled')->willReturn(true);

        $this->result->method('getSpellcheck')
            ->willReturn($spellcheckResult);

        $searchResult = $this->searcher->search($query);

        $this->assertNull(
            $searchResult->spellcheck,
            'unexpected spellcheck results',
        );
    }

    /**
     * @throws Exception
     */
    public function testSelectWithSpellcheckAndWithoutCollection(): void
    {

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: true,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $spellcheckResult = $this->createStub(SpellcheckResult::class);
        $spellcheckResult->method('getCorrectlySpelled')->willReturn(false);

        $suggest = $this->createStub(SolrSpellcheckSuggestion::class);
        $suggest->method('getOriginalTerm')->willReturn('abc');
        $suggest->method('getOriginalFrequency')->willReturn(3);
        $suggest->method('getWord')->willReturn('cde');
        $suggest->method('getFrequency')->willReturn(12);

        $spellcheckResult->method('getSuggestions')
            ->willReturn([$suggest]);

        $this->result->method('getSpellcheck')
            ->willReturn($spellcheckResult);

        $this->solrQuery->expects($this->once())
            ->method('getSpellcheck');

        $searchResult = $this->searcher->search($query);

        $expected = new Spellcheck(
            [new SpellcheckSuggestion(
                original: new SpellcheckWord('abc', 3),
                suggestion: new SpellcheckWord('cde', 12),
            )],
            '',
        );

        $this->assertEquals(
            $expected,
            $searchResult->spellcheck,
            'unexpected spellcheck results',
        );
    }

    public function testSetTimeZone(): void
    {
        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: new DateTimeZone("UTC"),
            boosting: null,
            distanceReferencePoint: null,
        );

        $this->solrQuery->expects($this->once())
            ->method('setTimezone')
            ->with(new DateTimeZone('UTC'));

        $this->searcher->search($query);
    }

    public function testSetDefaultTimeZone(): void
    {
        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $this->solrQuery->expects($this->once())
            ->method('setTimezone')
            ->with(date_default_timezone_get());

        $this->searcher->search($query);
    }

    public function testExplain(): void
    {
        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: false,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
            explain: true,
        );

        $this->solrQuery
            ->expects($this->once())
            ->method('addField')
            ->with('explain:[explain style=nl]');

        $this->searcher->search($query);
    }
}
