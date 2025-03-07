<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Dto\Search\Query;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Boosting;
use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\GeoPoint;
use Atoolo\Search\Dto\Search\Query\QueryOperator;
use Atoolo\Search\Dto\Search\Query\SearchQueryBuilder;
use Atoolo\Search\Dto\Search\Query\Sort\Criteria;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(SearchQueryBuilder::class)]
class SearchQueryBuilderTest extends TestCase
{
    private SearchQueryBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new SearchQueryBuilder();
    }

    public function testSetText(): void
    {
        $this->builder->text('abc');
        $query = $this->builder->build();
        $this->assertEquals('abc', $query->text, 'unexpected text');
    }

    public function testSetLang(): void
    {
        $this->builder->lang(ResourceLanguage::of('en'));
        $query = $this->builder->build();
        $this->assertEquals(
            ResourceLanguage::of('en'),
            $query->lang,
            'unexpected lang',
        );
    }

    public function testSetOffset(): void
    {
        $this->builder->offset(10);
        $query = $this->builder->build();
        $this->assertEquals(10, $query->offset, 'unexpected offset');
    }

    public function testSetInvalidOffset(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->offset(-1);
    }

    public function testSetLimit(): void
    {
        $this->builder->limit(10);
        $query = $this->builder->build();
        $this->assertEquals(10, $query->limit, 'unexpected limit');
    }

    public function testSetInvalidLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->builder->limit(-1);
    }

    public function testSetSpellcheck(): void
    {
        $this->builder->spellcheck(true);
        $query = $this->builder->build();
        $this->assertTrue($query->spellcheck, 'spellcheck should be true');
    }

    public function testSetArchive(): void
    {
        $this->builder->archive(true);
        $query = $this->builder->build();
        $this->assertTrue($query->archive, 'archive should be true');
    }

    /**
     * @throws Exception
     */
    public function testSetSort(): void
    {
        $criteria = $this->createStub(Criteria::class);

        $this->builder->sort($criteria);
        $query = $this->builder->build();
        $this->assertEquals([$criteria], $query->sort, 'unexpected sort');
    }

    public function testSetFilter(): void
    {
        $filter = $this->getMockBuilder(Filter::class)
            ->setConstructorArgs(['test'])
            ->getMock();
        $this->builder->filter($filter);
        $query = $this->builder->build();
        $this->assertEquals([$filter], $query->filter, 'unexpected filter');
    }

    public function testSetTwoFilterWithSameKey(): void
    {
        $filterA = $this->getMockBuilder(Filter::class)
            ->setConstructorArgs(['test'])
            ->getMock();
        $filterB = $this->getMockBuilder(Filter::class)
            ->setConstructorArgs(['test'])
            ->getMock();

        $this->expectException(InvalidArgumentException::class);
        $this->builder->filter($filterA, $filterB);
    }

    public function testSetFacet(): void
    {
        $facet = $this->getMockBuilder(Facet::class)
            ->setConstructorArgs(['test'])
            ->getMock();
        $this->builder->facet($facet);
        $query = $this->builder->build();
        $this->assertEquals([$facet], $query->facets, 'unexpected facets');
    }

    public function testSetTwoFacetSWithSameKey(): void
    {
        $facetA = $this->getMockBuilder(Facet::class)
            ->setConstructorArgs(['test'])
            ->getMock();
        $facetB = $this->getMockBuilder(Facet::class)
            ->setConstructorArgs(['test'])
            ->getMock();

        $this->expectException(InvalidArgumentException::class);
        $this->builder->facet($facetA, $facetB);
    }

    public function testSetQueryDefaultOperator(): void
    {
        $this->builder->defaultQueryOperator(QueryOperator::AND);
        $query = $this->builder->build();
        $this->assertEquals(
            QueryOperator::AND,
            $query->defaultQueryOperator,
            'unexpected queryDefaultOperator',
        );
    }

    public function testSetTimeZone(): void
    {
        $timeZone = new DateTimeZone('UTC');
        $this->builder->timeZone($timeZone);
        $query = $this->builder->build();
        $this->assertEquals(
            $timeZone,
            $query->timeZone,
            'unexpected timeZone',
        );
    }
    public function testSetBoosting(): void
    {
        $boosting = new Boosting();
        $this->builder->boosting($boosting);
        $query = $this->builder->build();
        $this->assertSame(
            $boosting,
            $query->boosting,
            'unexpected boosting',
        );
    }

    public function testSetDistanceReferencePoint(): void
    {
        $point = new GeoPoint(1, 2);
        $this->builder->distanceReferencePoint($point);
        $query = $this->builder->build();
        $this->assertSame(
            $point,
            $query->distanceReferencePoint,
            'unexpected distanceReferencePoint',
        );
    }

    public function testSetExplain(): void
    {
        $this->builder->explain(true);
        $query = $this->builder->build();
        $this->assertTrue(
            $query->explain,
            'unexpected explain',
        );
    }
}
