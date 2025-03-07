<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Search\Dto\Search\Query\Filter\AbsoluteDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\AndFilter;
use Atoolo\Search\Dto\Search\Query\Filter\FieldFilter;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\Filter\GeoLocatedFilter;
use Atoolo\Search\Dto\Search\Query\Filter\NotFilter;
use Atoolo\Search\Dto\Search\Query\Filter\OrFilter;
use Atoolo\Search\Dto\Search\Query\Filter\QueryFilter;
use Atoolo\Search\Dto\Search\Query\Filter\RelativeDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialArbitraryRectangleFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialOrbitalFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialOrbitalMode;
use Atoolo\Search\Dto\Search\Query\Filter\TeaserPropertyFilter;
use Atoolo\Search\Dto\Search\Query\GeoPoint;
use Atoolo\Search\Service\Search\Schema2xFieldMapper;
use Atoolo\Search\Service\Search\SolrQueryFilterAppender;
use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;

#[CoversClass(SolrQueryFilterAppender::class)]
class SolrQueryFilterAppenderTest extends TestCase
{
    private SolrQueryFilterAppender $appender;

    private FilterQuery&MockObject $filterQuery;

    public function setUp(): void
    {
        $this->filterQuery = $this->createMock(FilterQuery::class);
        $solrQuery = $this->createMock(SolrSelectQuery::class);
        $solrQuery->method('createFilterQuery')
            ->willReturn($this->filterQuery);
        $fieldMapper = $this->createMock(Schema2xFieldMapper::class);
        $fieldMapper->method('getFilterField')
            ->willReturn('test');
        $fieldMapper->method('getArchiveField')
            ->willReturn('archive');
        $this->appender = new SolrQueryFilterAppender($solrQuery, $fieldMapper);
    }

    public function testExcludeArchived(): void
    {
        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('-archive:true');

        $this->appender->excludeArchived();
    }

    public function testFieldFilterWithOneField(): void
    {
        $field = new FieldFilter(['a']);

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('test:a');

        $this->appender->append($field);
    }

    public function testFieldFilterWithTwoFields(): void
    {
        $field = new FieldFilter(['a', 'b']);

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('test:(a b)');

        $this->appender->append($field);
    }

    public function testAndFilter(): void
    {
        $a = new FieldFilter(['a']);
        $b = new FieldFilter(['b']);

        $filter = new AndFilter([$a, $b]);

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('(test:a AND test:b)');

        $this->appender->append($filter);
    }

    public function testOrFilter(): void
    {
        $a = new FieldFilter(['a']);
        $b = new FieldFilter(['b']);

        $filter = new OrFilter([$a, $b]);

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('(test:a OR test:b)');

        $this->appender->append($filter);
    }

    public function testNotFilter(): void
    {
        $a = new FieldFilter(['a']);

        $filter = new NotFilter($a);

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('NOT test:a');

        $this->appender->append($filter);
    }

    public function testQueryFilter(): void
    {
        $filter = new QueryFilter('a:b');

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('a:b');

        $this->appender->append($filter);
    }

    public function testTeaserPropertyFilter(): void
    {
        $filter = new TeaserPropertyFilter(
            image: true,
            imageCopyright: false,
            headline: true,
            text: false,
        );

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('(test:teaserImage AND -test:teaserImageCopyright AND test:teaserHeadline AND -test:teaserText)');

        $this->appender->append($filter);
    }

    public function testEmptyTeaserPropertyFilter(): void
    {
        $filter = new TeaserPropertyFilter(
            image: null,
            imageCopyright: null,
            headline: null,
            text: null,
        );

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('');

        $this->appender->append($filter);
    }


    public function testAbsoluteDateRangeFilterWithFromAndTo(): void
    {
        $from = new \DateTime('2021-01-01 00:00:00Z');
        $to = new \DateTime('2021-01-02 00:00:00Z');
        $filter = new AbsoluteDateRangeFilter($from, $to, 'sp_date_list');

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('test:[2021-01-01T00:00:00Z TO 2021-01-02T00:00:00Z]');

        $this->appender->append($filter);
    }

    public function testAbsoluteDateRangeFilterWithFrom(): void
    {
        $from = new \DateTime('2021-01-01 00:00:00Z');
        $filter = new AbsoluteDateRangeFilter($from, null, 'sp_date_list');

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('test:[2021-01-01T00:00:00Z TO *]');

        $this->appender->append($filter);
    }

    public function testAbsoluteDateRangeFilterWithTo(): void
    {
        $to = new \DateTime('2021-01-02 00:00:00Z');
        $filter = new AbsoluteDateRangeFilter(null, $to, 'sp_date_list');

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('test:[* TO 2021-01-02T00:00:00Z]');

        $this->appender->append($filter);
    }

    public function testGeoLocatedFilterExistsTrue(): void
    {
        $filter = new GeoLocatedFilter(true);

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('test:*');

        $this->appender->append($filter);
    }

    public function testGeoLocatedFilterExistsFalse(): void
    {
        $filter = new GeoLocatedFilter(false);

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('-test:*');

        $this->appender->append($filter);
    }

    public function testSpatialOrbitalFilterWithGreateCircleDistanceMode(): void
    {
        $filter = new SpatialOrbitalFilter(
            4,
            new GeoPoint(1, 2),
            SpatialOrbitalMode::GREAT_CIRCLE_DISTANCE,
        );

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('{!geofilt sfield=test pt=2,1 d=4}');

        $this->appender->append($filter);
    }

    public function testSpatialOrbitalFilterWithBoundingBoxMode(): void
    {
        $filter = new SpatialOrbitalFilter(
            4,
            new GeoPoint(1, 2),
            SpatialOrbitalMode::BOUNDING_BOX,
        );

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('{!bbox sfield=test pt=2,1 d=4}');

        $this->appender->append($filter);
    }

    public function testSpatialArbitraryRectangleFilter(): void
    {
        $filter = new SpatialArbitraryRectangleFilter(
            new GeoPoint(1, 2),
            new GeoPoint(3, 4),
        );

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with('test:[ 2,1 TO 4,3 ]');

        $this->appender->append($filter);
    }

    /**
     * @return array<array{string, string}>
     */
    public static function additionProviderForBeforeIntervals(): array
    {
        return [
            ['P1D', 'test:[NOW-1DAYS/DAY TO NOW/DAY+1DAY-1SECOND]'],
            ['P1W', 'test:[NOW-7DAYS/DAY TO NOW/DAY+1DAY-1SECOND]'],
            ['P2M', 'test:[NOW-2MONTHS/DAY TO NOW/DAY+1DAY-1SECOND]'],
            ['P3Y', 'test:[NOW-3YEARS/DAY TO NOW/DAY+1DAY-1SECOND]'],
        ];
    }

    /**
     * @return array<array{string, string}>
     */
    public static function additionProviderForAfterIntervals(): array
    {
        return [
            ['P1D', 'test:[NOW/DAY TO NOW+1DAYS/DAY+1DAY-1SECOND]'],
            ['P1W', 'test:[NOW/DAY TO NOW+7DAYS/DAY+1DAY-1SECOND]'],
            ['P2M', 'test:[NOW/DAY TO NOW+2MONTHS/DAY+1DAY-1SECOND]'],
            ['P3Y', 'test:[NOW/DAY TO NOW+3YEARS/DAY+1DAY-1SECOND]'],
        ];
    }

    /**
     * @return array<array{string, string, string}>
     */
    public static function additionProviderForBeforeAndAfterIntervals(): array
    {
        return [
            [
                'P1D',
                'P1D',
                'test:[NOW-1DAYS/DAY TO NOW+1DAYS/DAY+1DAY-1SECOND]',
            ],
            [
                'P1W',
                'P2M',
                'test:[NOW-7DAYS/DAY TO NOW+2MONTHS/DAY+1DAY-1SECOND]',
            ],
        ];
    }

    /**
     * @return array<array{DateTime, ?string, ?string, string}>
     */
    public static function additionProviderWithBase(): array
    {
        return [
            [
                new DateTime('2021-01-01 00:00:00Z'),
                'P1D',
                null,
                'test:[2021-01-01T00:00:00Z-1DAYS/DAY' .
                ' TO 2021-01-01T00:00:00Z/DAY+1DAY-1SECOND]',
            ],
            [
                new DateTime('2021-01-01 00:00:00Z'),
                null,
                'P2M',
                'test:[2021-01-01T00:00:00Z/DAY' .
                ' TO 2021-01-01T00:00:00Z+2MONTHS/DAY+1DAY-1SECOND]',
            ],
            [
                new DateTime('2021-01-01 00:00:00Z'),
                'P1W',
                'P2M',
                'test:[2021-01-01T00:00:00Z-7DAYS/DAY' .
                ' TO 2021-01-01T00:00:00Z+2MONTHS/DAY+1DAY-1SECOND]',
            ],
        ];
    }

    /**
     * @return array<array{string}>
     */
    public static function additionProviderForInvalidIntervals(): array
    {
        return [
            ['PT1H'],
            ['PT1M'],
            ['PT1S'],
        ];
    }

    /**
     * @throws Exception
     */
    #[DataProvider('additionProviderForBeforeIntervals')]
    public function testGetQueryWithFrom(
        string $before,
        string $expected,
    ): void {
        $filter = new RelativeDateRangeFilter(
            null,
            new DateInterval($before),
            null,
            null,
            null,
        );

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with($expected);

        $this->appender->append($filter);
    }

    /**
     * @throws Exception
     */
    #[DataProvider('additionProviderForAfterIntervals')]
    public function testGetQueryWithTo(
        string $after,
        string $expected,
    ): void {
        $filter = new RelativeDateRangeFilter(
            null,
            null,
            new DateInterval($after),
            null,
            null,
        );

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with($expected);

        $this->appender->append($filter);
    }

    /**
     * @throws Exception
     */
    #[DataProvider('additionProviderForBeforeAndAfterIntervals')]
    public function testGetQueryWithFromAndTo(
        string $before,
        string $after,
        string $expected,
    ): void {
        $filter = new RelativeDateRangeFilter(
            null,
            new DateInterval($before),
            new DateInterval($after),
            null,
            null,
        );

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with($expected);

        $this->appender->append($filter);
    }

    /**
     * @throws Exception
     */
    #[DataProvider('additionProviderWithBase')]
    public function testGetQueryWithBase(
        DateTime $base,
        ?string $before,
        ?string $after,
        string $expected,
    ): void {
        $filter = new RelativeDateRangeFilter(
            $base,
            $before === null ? null : new DateInterval($before),
            $after === null ? null : new DateInterval($after),
            null,
            null,
        );

        $this->filterQuery->expects($this->once())
            ->method('setQuery')
            ->with($expected);

        $this->appender->append($filter);
    }

    /**
     * @throws Exception
     */
    #[DataProvider('additionProviderForInvalidIntervals')]
    public function testGetQueryWithInvalidIntervals(
        string $interval,
    ): void {
        $filter = new RelativeDateRangeFilter(
            null,
            null,
            new DateInterval($interval),
            null,
            null,
        );
        $this->expectException(InvalidArgumentException::class);
        $this->appender->append($filter);
    }

    public function testUnsupportedFilter(): void
    {
        $filter = $this->createStub(Filter::class);
        $this->expectException(InvalidArgumentException::class);
        $this->appender->append($filter);
    }
}
