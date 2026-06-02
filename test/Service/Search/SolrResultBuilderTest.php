<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Facet\ObjectTypeFacet;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Result\Facet;
use Atoolo\Search\Dto\Search\Result\FacetGroup;
use Atoolo\Search\Service\Search\SolrResultBuilder;
use Atoolo\Search\Service\Search\SolrResultToResourceResolver;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Solarium\Component\Result\FacetSet;
use Solarium\Component\Result\Facet\Field as SolrFacetField;
use Solarium\Component\Result\Facet\Query as SolrFacetQuery;
use Solarium\Component\Result\Spellcheck\Result as SpellcheckResult;
use Solarium\Component\Result\Spellcheck\Suggestion;
use Solarium\Component\Result\Spellcheck\Collation;
use Solarium\QueryType\Select\Result\Result as SelectResult;

#[CoversClass(SolrResultBuilder::class)]
class SolrResultBuilderTest extends TestCase
{
    private SolrResultBuilder $builder;
    private SolrResultToResourceResolver&MockObject $resourceResolver;

    protected function setUp(): void
    {
        $this->resourceResolver = $this->createMock(SolrResultToResourceResolver::class);
        $this->builder = new SolrResultBuilder($this->resourceResolver);
    }

    public function testBuildResult(): void
    {
        $query = new SearchQuery(
            text: 'test',
            lang: ResourceLanguage::default(),
            offset: 5,
            limit: 10,
            sort: [],
            filter: [],
            facets: [new ObjectTypeFacet('objectType', [])],
            spellcheck: false,
            archive: false,
            expandByDate: false,
            defaultQueryOperator: \Atoolo\Search\Dto\Search\Query\QueryOperator::AND,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $result = $this->createMock(SelectResult::class);
        $lang = ResourceLanguage::default();

        $resources = [$this->createMock(Resource::class)];
        $facetGroups = [new FacetGroup('test', [])];

        $this->resourceResolver
            ->expects($this->once())
            ->method('loadResourceList')
            ->with($result, $lang)
            ->willReturn($resources);

        $result
            ->expects($this->once())
            ->method('getNumFound')
            ->willReturn(42);

        $result
            ->expects($this->once())
            ->method('getQueryTime')
            ->willReturn(123);

        // Mock facet set
        $facetSet = $this->createMock(FacetSet::class);
        $result
            ->expects($this->once())
            ->method('getFacetSet')
            ->willReturn($facetSet);

        $solrFacetField = $this->createMock(SolrFacetField::class);
        $facetSet
            ->expects($this->once())
            ->method('getFacet')
            ->with('objectType')
            ->willReturn($solrFacetField);

        // Mock facet field iteration
        $solrFacetField
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator(['news' => 5, 'article' => 3]));

        // Mock spellcheck
        $result
            ->expects($this->once())
            ->method('getSpellcheck')
            ->willReturn(null);

        $searchResult = $this->builder->buildResult($query, $result, $lang);

        $this->assertEquals(42, $searchResult->total);
        $this->assertEquals(10, $searchResult->limit);
        $this->assertEquals(5, $searchResult->offset);
        $this->assertEquals(123, $searchResult->queryTime);
        $this->assertSame($resources, $searchResult->results);
        $this->assertCount(1, $searchResult->facetGroups);
        $this->assertNull($searchResult->spellcheck);
    }

    public function testBuildExpandedResult(): void
    {
        $query = new SearchQuery(
            text: 'test',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [],
            spellcheck: false,
            archive: false,
            expandByDate: true,
            defaultQueryOperator: \Atoolo\Search\Dto\Search\Query\QueryOperator::AND,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $parentResult = $this->createMock(SelectResult::class);
        $childResult = $this->createMock(SelectResult::class);
        $parentFacetGroups = [new FacetGroup('parent', [])];
        $childFacetGroups = [new FacetGroup('child', [])];
        $lang = ResourceLanguage::default();

        $resources = [$this->createMock(Resource::class)];

        $this->resourceResolver
            ->expects($this->once())
            ->method('loadResourceList')
            ->with($childResult, $lang)
            ->willReturn($resources);

        $childResult
            ->expects($this->exactly(2))
            ->method('getNumFound')
            ->willReturn(25);

        $childResult
            ->expects($this->exactly(2))
            ->method('getQueryTime')
            ->willReturn(50);

        $parentResult
            ->expects($this->once())
            ->method('getQueryTime')
            ->willReturn(30);

        $childResult
            ->expects($this->once())
            ->method('getFacetSet')
            ->willReturn(null);

        $parentResult
            ->expects($this->once())
            ->method('getSpellcheck')
            ->willReturn(null);

        $childResult
            ->expects($this->once())
            ->method('getSpellcheck')
            ->willReturn(null);

        $searchResult = $this->builder->buildExpandedResult(
            $query,
            $parentResult,
            $childResult,
            $parentFacetGroups,
            $childFacetGroups,
            $lang,
        );

        $this->assertEquals(25, $searchResult->total);
        $this->assertEquals(10, $searchResult->limit);
        $this->assertEquals(0, $searchResult->offset);
        $this->assertEquals(80, $searchResult->queryTime); // 50 + 30
        $this->assertSame($resources, $searchResult->results);
        $this->assertCount(2, $searchResult->facetGroups); // parent + child merged
        $this->assertNull($searchResult->spellcheck);
    }

    public function testBuildFacetGroupListEmpty(): void
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
            expandByDate: false,
            defaultQueryOperator: \Atoolo\Search\Dto\Search\Query\QueryOperator::AND,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $result = $this->createMock(SelectResult::class);
        $result
            ->expects($this->once())
            ->method('getFacetSet')
            ->willReturn(null);

        $facetGroups = $this->builder->buildFacetGroupList($query, $result);

        $this->assertEmpty($facetGroups);
    }
    public function testBuildFacetGroupListInvalidKey(): void
    {
        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [new ObjectTypeFacet('test', [])],
            spellcheck: false,
            archive: false,
            expandByDate: false,
            defaultQueryOperator: \Atoolo\Search\Dto\Search\Query\QueryOperator::AND,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );
        $facetSet = $this->createMock(FacetSet::class);
        $result = $this->createMock(SelectResult::class);
        $result
            ->expects($this->once())
            ->method('getFacetSet')
            ->willReturn($facetSet);

        $facetGroups = $this->builder->buildFacetGroupList($query, $result);

        $this->assertEmpty($facetGroups);
    }

    public function testBuildFacetGroupByField(): void
    {
        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [new ObjectTypeFacet('test', [])],
            spellcheck: false,
            archive: false,
            expandByDate: false,
            defaultQueryOperator: \Atoolo\Search\Dto\Search\Query\QueryOperator::AND,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $result = $this->createMock(SelectResult::class);
        $facetSet = $this->createMock(FacetSet::class);
        $solrFacetField = $this->createMock(SolrFacetField::class);

        $result
            ->expects($this->once())
            ->method('getFacetSet')
            ->willReturn($facetSet);

        $facetSet
            ->expects($this->once())
            ->method('getFacet')
            ->with('test')
            ->willReturn($solrFacetField);

        $solrFacetField
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator(['news' => 10, 'article' => 5]));

        $facetGroups = $this->builder->buildFacetGroupList($query, $result);

        $this->assertCount(1, $facetGroups);
        $this->assertEquals('test', $facetGroups[0]->key);
        $this->assertCount(2, $facetGroups[0]->facets);
        $this->assertEquals('news', $facetGroups[0]->facets[0]->key);
        $this->assertEquals(10, $facetGroups[0]->facets[0]->hits);
    }

    public function testBuildFacetGroupByFieldInvalidCount(): void
    {
        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [new ObjectTypeFacet('test', [])],
            spellcheck: false,
            archive: false,
            expandByDate: false,
            defaultQueryOperator: \Atoolo\Search\Dto\Search\Query\QueryOperator::AND,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $result = $this->createMock(SelectResult::class);
        $facetSet = $this->createMock(FacetSet::class);
        $solrFacetField = $this->createMock(SolrFacetField::class);

        $result
            ->expects($this->once())
            ->method('getFacetSet')
            ->willReturn($facetSet);

        $facetSet
            ->expects($this->once())
            ->method('getFacet')
            ->with('test')
            ->willReturn($solrFacetField);

        $solrFacetField
            ->expects($this->once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator(['news' => 'invalid']));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('facet count should be a int: invalid');

        $this->builder->buildFacetGroupList($query, $result);
    }

    public function testBuildFacetGroupByQuery(): void
    {
        $query = new SearchQuery(
            text: '',
            lang: ResourceLanguage::default(),
            offset: 0,
            limit: 10,
            sort: [],
            filter: [],
            facets: [new ObjectTypeFacet('test', [])],
            spellcheck: false,
            archive: false,
            expandByDate: false,
            defaultQueryOperator: \Atoolo\Search\Dto\Search\Query\QueryOperator::AND,
            timeZone: null,
            boosting: null,
            distanceReferencePoint: null,
        );

        $result = $this->createMock(SelectResult::class);
        $facetSet = $this->createMock(FacetSet::class);
        $solrFacetQuery = $this->createMock(SolrFacetQuery::class);

        $result
            ->expects($this->once())
            ->method('getFacetSet')
            ->willReturn($facetSet);

        $facetSet
            ->expects($this->once())
            ->method('getFacet')
            ->with('test')
            ->willReturn($solrFacetQuery);

        $solrFacetQuery
            ->expects($this->once())
            ->method('getValue')
            ->willReturn(42);

        $facetGroups = $this->builder->buildFacetGroupList($query, $result);

        $this->assertCount(1, $facetGroups);
        $this->assertEquals('test', $facetGroups[0]->key);
        $this->assertCount(1, $facetGroups[0]->facets);
        $this->assertEquals('test', $facetGroups[0]->facets[0]->key);
        $this->assertEquals(42, $facetGroups[0]->facets[0]->hits);
    }

    public function testBuildSpellcheckNull(): void
    {
        $result = $this->createMock(SelectResult::class);
        $result
            ->expects($this->once())
            ->method('getSpellcheck')
            ->willReturn(null);

        $spellcheck = $this->builder->buildSpellcheck($result);

        $this->assertNull($spellcheck);
    }

    public function testBuildSpellcheckCorrectlySpelled(): void
    {
        $result = $this->createMock(SelectResult::class);
        $spellcheckResult = $this->createMock(SpellcheckResult::class);

        $result
            ->expects($this->once())
            ->method('getSpellcheck')
            ->willReturn($spellcheckResult);

        $spellcheckResult
            ->expects($this->once())
            ->method('getCorrectlySpelled')
            ->willReturn(true);

        $spellcheck = $this->builder->buildSpellcheck($result);

        $this->assertNull($spellcheck);
    }

    public function testBuildSpellcheckWithSuggestions(): void
    {
        $result = $this->createMock(SelectResult::class);
        $spellcheckResult = $this->createMock(SpellcheckResult::class);
        $suggestion = $this->createMock(Suggestion::class);
        $collation = $this->createMock(Collation::class);

        $result
            ->expects($this->once())
            ->method('getSpellcheck')
            ->willReturn($spellcheckResult);

        $spellcheckResult
            ->expects($this->once())
            ->method('getCorrectlySpelled')
            ->willReturn(false);

        $spellcheckResult
            ->expects($this->once())
            ->method('getSuggestions')
            ->willReturn([$suggestion]);

        $suggestion
            ->expects($this->once())
            ->method('getOriginalTerm')
            ->willReturn('wrng');

        $suggestion
            ->expects($this->once())
            ->method('getOriginalFrequency')
            ->willReturn(0);

        $suggestion
            ->expects($this->once())
            ->method('getWord')
            ->willReturn('wrong');

        $suggestion
            ->expects($this->once())
            ->method('getFrequency')
            ->willReturn(5);

        $spellcheckResult
            ->method('getCollation')
            ->willReturn($collation);

        $collation
            ->expects($this->once())
            ->method('getQuery')
            ->willReturn('corrected query');

        $spellcheck = $this->builder->buildSpellcheck($result);

        $this->assertNotNull($spellcheck);
        $this->assertCount(1, $spellcheck->suggestions);
        $this->assertEquals('corrected query', $spellcheck->collation);
        $this->assertEquals('wrng', $spellcheck->suggestions[0]->original->word);
        $this->assertEquals('wrong', $spellcheck->suggestions[0]->suggestion->word);
    }

    public function testBuildSpellcheckWithoutCollation(): void
    {
        $result = $this->createMock(SelectResult::class);
        $spellcheckResult = $this->createMock(SpellcheckResult::class);

        $result
            ->expects($this->once())
            ->method('getSpellcheck')
            ->willReturn($spellcheckResult);

        $spellcheckResult
            ->expects($this->once())
            ->method('getCorrectlySpelled')
            ->willReturn(false);

        $spellcheckResult
            ->expects($this->once())
            ->method('getSuggestions')
            ->willReturn([]);

        $spellcheckResult
            ->expects($this->once())
            ->method('getCollation')
            ->willReturn(null);

        $spellcheck = $this->builder->buildSpellcheck($result);

        $this->assertNotNull($spellcheck);
        $this->assertEmpty($spellcheck->suggestions);
        $this->assertEquals('', $spellcheck->collation);
    }
}
