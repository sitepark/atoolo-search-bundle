<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Search\Dto\Search\Query\Facet\AbsoluteDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Facet\MultiQueryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ObjectTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\QueryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\RelativeDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\SpatialDistanceRangeFacet;
use Atoolo\Search\Dto\Search\Query\GeoPoint;
use Atoolo\Search\Service\Search\Schema2xFieldMapper;
use Atoolo\Search\Service\Search\SolrQueryFacetAppender;
use DateInterval;
use DateTime;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Solarium\Component\Facet\Field;
use Solarium\Component\Facet\MultiQuery;
use Solarium\Component\Facet\Query;
use Solarium\Component\Facet\Range;
use Solarium\Component\FacetSet;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;

#[CoversClass(SolrQueryFacetAppender::class)]
class SolrQueryFacetAppenderTest extends TestCase
{
    private SolrQueryFacetAppender $appender;

    private Field&MockObject $facetField;

    private Query&MockObject $facetQuery;

    private MultiQuery&MockObject $facetMultiQuery;

    private Range&MockObject $facetRage;

    public function setUp(): void
    {
        $this->facetField = $this->createMock(Field::class);
        $this->facetQuery = $this->createMock(Query::class);
        $this->facetQuery->method('setQuery')
            ->willReturn($this->facetQuery);
        $this->facetMultiQuery = $this->createMock(MultiQuery::class);
        $this->facetRage = $this->createMock(Range::class);
        $this->facetRage->method('setField')
            ->willReturn($this->facetRage);
        $this->facetRage->method('setStart')
            ->willReturn($this->facetRage);
        $this->facetRage->method('setEnd')
            ->willReturn($this->facetRage);
        $this->facetRage->method('setGap')
            ->willReturn($this->facetRage);

        $facetSet = $this->createStub(FacetSet::class);
        $facetSet->method('createFacetField')
            ->willReturn($this->facetField);
        $facetSet->method('createFacetQuery')
            ->willReturn($this->facetQuery);
        $facetSet->method('createFacetMultiQuery')
            ->willReturn($this->facetMultiQuery);
        $facetSet->method('createFacetRange')
            ->willReturn($this->facetRage);

        $solrQuery = $this->createMock(SolrSelectQuery::class);
        $solrQuery->method('getFacetSet')
            ->willReturn($facetSet);

        $fieldMapper = $this->createMock(Schema2xFieldMapper::class);
        $fieldMapper->method('getFacetField')
            ->willReturn('test');
        $this->appender = new SolrQueryFacetAppender($solrQuery, $fieldMapper);
    }

    public function testAppendFieldFacet(): void
    {
        $this->facetField->expects($this->once())
            ->method('setField')
            ->with('test');
        $this->facetField->expects($this->once())
            ->method('setTerms')
            ->with(['a']);
        $this->facetField->expects($this->once())
            ->method('setExcludes')
            ->with(['exclude']);
        $this->appender->append(new ObjectTypeFacet('key', ['a'], ['exclude']));
    }

    public function testAppendQueryFacet(): void
    {
        $this->facetQuery->expects($this->once())
            ->method('setQuery')
            ->with('test:a');
        $this->facetQuery->expects($this->once())
            ->method('setExcludes')
            ->with(['exclude']);
        $this->appender->append(new QueryFacet('key', 'test:a', ['exclude']));
    }

    public function testAppendMultiQueryFacet(): void
    {
        $this->facetMultiQuery->expects($this->once())
            ->method('createQuery')
            ->with('key', 'test:a');
        $this->facetMultiQuery->expects($this->once())
            ->method('setExcludes')
            ->with(['exclude']);
        $this->appender->append(
            new MultiQueryFacet(
                'key',
                [ new QueryFacet('key', 'test:a', ['exclude']) ],
                ['exclude'],
            ),
        );
    }

    public function testAppendAbsoluteDateRangeFacetWithOutGap(): void
    {
        $this->facetQuery->expects($this->once())
            ->method('setQuery')
            ->with('test:[2021-01-02T00:00:00Z TO 2021-01-03T00:00:00Z]');
        $this->facetQuery->expects($this->once())
            ->method('setExcludes')
            ->with(['exclude']);
        $this->appender->append(
            new AbsoluteDateRangeFacet(
                'key',
                new DateTime('2021-01-02T00:00:00Z'),
                new DateTime('2021-01-03T00:00:00Z'),
                null,
                ['exclude'],
            ),
        );
    }

    public function testAppendAbsoluteDateRangeFacetWithGap(): void
    {
        $this->facetRage->expects($this->once())
            ->method('setField')
            ->with('test');
        $this->facetRage->expects($this->once())
            ->method('setStart')
            ->with('2021-01-01T00:00:00Z');
        $this->facetRage->expects($this->once())
            ->method('setEnd')
            ->with('2021-01-03T00:00:00Z');
        $this->facetRage->expects($this->once())
            ->method('setGap')
            ->with('+1DAYS');
        $this->facetRage->expects($this->once())
            ->method('setExcludes')
            ->with(['exclude']);
        $this->appender->append(
            new AbsoluteDateRangeFacet(
                'key',
                new DateTime('2021-01-01T00:00:00Z'),
                new DateTime('2021-01-03T00:00:00Z'),
                new DateInterval('P1D'),
                ['exclude'],
            ),
        );
    }

    public function testAppendRelativeDateRangeFacetWithoutGap(): void
    {
        $this->facetQuery->expects($this->once())
            ->method('setQuery')
            ->with(
                'test:[' .
                '2021-01-01T00:00:00Z-2DAYS/DAY' .
                ' TO ' .
                '2021-01-01T00:00:00Z+3DAYS/DAY+1DAY-1SECOND' .
                ']',
            );
        $this->facetQuery->expects($this->once())
            ->method('setExcludes')
            ->with(['exclude']);
        $this->appender->append(
            new RelativeDateRangeFacet(
                'key',
                new DateTime('2021-01-01T00:00:00Z'),
                new DateInterval('P2D'),
                new DateInterval('P3D'),
                null,
                null,
                null,
                ['exclude'],
            ),
        );
    }

    public function testAppendRelativeDateRangeFacetWithoutBeforeAndGap(): void
    {
        $this->facetQuery->expects($this->once())
            ->method('setQuery')
            ->with(
                'test:[' .
                '2021-01-01T00:00:00Z/DAY' .
                ' TO ' .
                '2021-01-01T00:00:00Z+3DAYS/DAY+1DAY-1SECOND' .
                ']',
            );
        $this->facetQuery->expects($this->once())
            ->method('setExcludes')
            ->with(['exclude']);
        $this->appender->append(
            new RelativeDateRangeFacet(
                'key',
                new DateTime('2021-01-01T00:00:00Z'),
                null,
                new DateInterval('P3D'),
                null,
                null,
                null,
                ['exclude'],
            ),
        );
    }

    public function testAppendRelativeDateRangeFacetWithoutAfterAndGap(): void
    {
        $this->facetQuery->expects($this->once())
            ->method('setQuery')
            ->with(
                'test:[' .
                '2021-01-01T00:00:00Z-2DAYS/DAY' .
                ' TO ' .
                '2021-01-01T00:00:00Z/DAY+1DAY-1SECOND' .
                ']',
            );
        $this->facetQuery->expects($this->once())
            ->method('setExcludes')
            ->with(['exclude']);
        $this->appender->append(
            new RelativeDateRangeFacet(
                'key',
                new DateTime('2021-01-01T00:00:00Z'),
                new DateInterval('P2D'),
                null,
                null,
                null,
                null,
                ['exclude'],
            ),
        );
    }

    public function testAppendRelativeDateRangeFacetWithGap(): void
    {
        $this->facetRage->expects($this->once())
            ->method('setField')
            ->with('test');
        $this->facetRage->expects($this->once())
            ->method('setStart')
            ->with('2021-01-01T00:00:00Z-2DAYS/DAY');
        $this->facetRage->expects($this->once())
            ->method('setEnd')
            ->with('2021-01-01T00:00:00Z+3DAYS/DAY+1DAY-1SECOND');
        $this->facetRage->expects($this->once())
            ->method('setGap')
            ->with('+1DAYS');
        $this->facetRage->expects($this->once())
            ->method('setExcludes')
            ->with(['exclude']);
        $this->appender->append(
            new RelativeDateRangeFacet(
                'key',
                new DateTime('2021-01-01T00:00:00Z'),
                new DateInterval('P2D'),
                new DateInterval('P3D'),
                new DateInterval('P1D'),
                null,
                null,
                ['exclude'],
            ),
        );
    }

    public function testAppendGeoDistanceRangeFacet(): void
    {
        $this->facetQuery->expects($this->once())
            ->method('setQuery')
            ->with('{!frange l=0 u=10}geodist(,10,10)');
        $this->facetQuery->expects($this->once())
            ->method('setExcludes')
            ->with(['geofilter']);

        $this->appender->append(
            new SpatialDistanceRangeFacet(
                'mykey',
                new GeoPoint(10, 10),
                0,
                10,
                ['geofilter'],
            ),
        );
    }

    public function testUnsupportedFacet(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->appender->append(
            $this->createStub(Facet::class),
        );
    }
}
