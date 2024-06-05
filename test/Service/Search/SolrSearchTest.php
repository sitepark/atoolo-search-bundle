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
use Atoolo\Search\Dto\Search\Query\QueryOperator;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Query\Sort\Date;
use Atoolo\Search\Dto\Search\Query\Sort\Headline;
use Atoolo\Search\Dto\Search\Query\Sort\Name;
use Atoolo\Search\Dto\Search\Query\Sort\Natural;
use Atoolo\Search\Dto\Search\Query\Sort\Score;
use Atoolo\Search\Dto\Search\Result\FacetGroup;
use Atoolo\Search\Service\IndexName;
use Atoolo\Search\Service\Search\Schema2xFieldMapper;
use Atoolo\Search\Service\Search\SolrQueryModifier;
use Atoolo\Search\Service\Search\SolrResultToResourceResolver;
use Atoolo\Search\Service\Search\SolrSearch;
use Atoolo\Search\Service\SolrClientFactory;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
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

#[CoversClass(SolrSearch::class)]
class SolrSearchTest extends TestCase
{
    private Resource|Stub $resource;

    private SelectResult|Stub $result;

    private SolrSelectQuery&MockObject $solrQuery;

    private SolrSearch $searcher;

    protected function setUp(): void
    {
        $indexName = $this->createStub(IndexName::class);
        $clientFactory = $this->createStub(
            SolrClientFactory::class
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
            SolrResultToResourceResolver::class
        );

        $solrQueryModifier = $this->createStub(SolrQueryModifier::class);
        $solrQueryModifier->method('modify')->willReturn($this->solrQuery);

        $resultToResourceResolver
            ->method('loadResourceList')
            ->willReturn([$this->resource]);

        $schemaFieldMapper = $this->createStub(
            Schema2xFieldMapper::class
        );

        $this->searcher = new SolrSearch(
            $indexName,
            $clientFactory,
            $resultToResourceResolver,
            $schemaFieldMapper,
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
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEquals(
            [$this->resource],
            $searchResult->results,
            'unexpected results'
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
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEquals(
            [$this->resource],
            $searchResult->results,
            'unexpected results'
        );
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
                new Headline(),
                new Date(),
                new Natural(),
                new Score(),
            ],
            filter: [],
            facets: [],
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEquals(
            [$this->resource],
            $searchResult->results,
            'unexpected results'
        );
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
            archive: false,
            defaultQueryOperator: QueryOperator::AND,
            timeZone: null,
            boosting: null
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEquals(
            [$this->resource],
            $searchResult->results,
            'unexpected results'
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
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEquals(
            [$this->resource],
            $searchResult->results,
            'unexpected results'
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
                ['ob']
            )
        ];

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: $facets,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEquals(
            [$this->resource],
            $searchResult->results,
            'unexpected results'
        );
    }

    public function testSelectWithInvalidFacets(): void
    {

        $facets = [
            $this->createStub(Facet::class)
        ];

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: $facets,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null
        );

        $this->expectException(InvalidArgumentException::class);
        $this->searcher->search($query);
    }

    public function testResulWithFacetField(): void
    {

        $facet = new SolrFacetField([
            'content' => 10,
            'media' => 5
        ]);
        $facetSet = new \Solarium\Component\Result\FacetSet([
            'objectType' => $facet
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
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null
        );

        $searchResult = $this->searcher->search($query);

        $expected = new FacetGroup(
            'objectType',
            [
                new \Atoolo\Search\Dto\Search\Result\Facet('content', 10),
                new \Atoolo\Search\Dto\Search\Result\Facet('media', 5)
            ]
        );

        $this->assertEquals(
            $expected,
            $searchResult->facetGroups[0],
            'unexpected facet results'
        );
    }

    public function testResultWithFacetQuery(): void
    {

        $facet = new SolrFacetQuery(5);
        $facetSet = new \Solarium\Component\Result\FacetSet([
            'aquery' => $facet
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
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null
        );

        $searchResult = $this->searcher->search($query);

        $expected = new FacetGroup(
            'aquery',
            [
               new \Atoolo\Search\Dto\Search\Result\Facet('aquery', 5)
            ]
        );

        $this->assertEquals(
            $expected,
            $searchResult->facetGroups[0],
            'unexpected facet results'
        );
    }
    public function testInvalidResultFacets(): void
    {

        $facet = new SolrFacetField([
            'content' => 'nonint',
        ]);
        $facetSet = new \Solarium\Component\Result\FacetSet([
            'objectType' => $facet
        ]);

        $this->result->method('getFacetSet')
            ->willReturn($facetSet);

        $facets = [
            new ObjectTypeFacet('objectType', ['content'], ['ob']),
            new QueryFacet('query', 'sp_id:123', ['ob']),
            new MultiQueryFacet(
                'multiquery',
                [new QueryFacet('query', 'sp_id:123')],
                ['ob']
            )
        ];

        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: $facets,
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null
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
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null
        );

        $searchResult = $this->searcher->search($query);

        $this->assertEmpty(
            $searchResult->facetGroups,
            'facets should be empty'
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
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: new DateTimeZone("UTC"),
            boosting: null
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
            archive: false,
            defaultQueryOperator: QueryOperator::OR,
            timeZone: null,
            boosting: null
        );

        $this->solrQuery->expects($this->once())
            ->method('setTimezone')
            ->with(date_default_timezone_get());

        $this->searcher->search($query);
    }
}
