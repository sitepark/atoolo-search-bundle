<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Dto\Search\Query\Filter;

use Atoolo\Search\Dto\Search\Query\Filter\RelativeDateRangeFilter;
use DateInterval;
use DateTime;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RelativeDateRangeFilter::class)]
class RelativeDateRangeFilterTest extends TestCase
{
    /**
     * @return array<array{string, string}>
     */
    public static function additionProviderForFromIntervals(): array
    {
        return [
            ['P1D', 'sp_date_list:[NOW-1DAYS/DAY TO NOW/DAY+1DAY-1SECOND]'],
            ['P1W', 'sp_date_list:[NOW-7DAYS/DAY TO NOW/DAY+1DAY-1SECOND]'],
            ['P2M', 'sp_date_list:[NOW-2MONTHS/DAY TO NOW/DAY+1DAY-1SECOND]'],
            ['P3Y', 'sp_date_list:[NOW-3YEARS/DAY TO NOW/DAY+1DAY-1SECOND]'],
        ];
    }

    /**
     * @return array<array{string, string}>
     */
    public static function additionProviderForToIntervals(): array
    {
        return [
            ['P1D', 'sp_date_list:[NOW/DAY TO NOW-1DAYS/DAY]'],
            ['P1W', 'sp_date_list:[NOW/DAY TO NOW-7DAYS/DAY]'],
            ['P2M', 'sp_date_list:[NOW/DAY TO NOW-2MONTHS/DAY]'],
            ['P3Y', 'sp_date_list:[NOW/DAY TO NOW-3YEARS/DAY]'],
        ];
    }

    /**
     * @return array<array{string, string, string}>
     */
    public static function additionProviderForFromAndToIntervals(): array
    {
        return [
            ['P1D', 'P1D', 'sp_date_list:[NOW-1DAYS/DAY TO NOW-1DAYS/DAY]'],
            ['P1W', 'P2M', 'sp_date_list:[NOW-7DAYS/DAY TO NOW-2MONTHS/DAY]'],
        ];
    }

    /**
     * @return array<array{DateTime, ?string, ?string, string}>
     */
    public static function additionProviderWithBase(): array
    {
        return [
            [
                new DateTime('2021-01-01 00:00:00'),
                'P1D',
                null,
                'sp_date_list:[2021-01-01T00:00:00Z-1DAYS/DAY' .
                    ' TO 2021-01-01T00:00:00Z/DAY+1DAY-1SECOND]'
            ],
            [
                new DateTime('2021-01-01 00:00:00'),
                null,
                'P2M',
                'sp_date_list:[2021-01-01T00:00:00Z/DAY' .
                    ' TO 2021-01-01T00:00:00Z-2MONTHS/DAY]'
            ],
            [
                new DateTime('2021-01-01 00:00:00'),
                'P1W',
                'P2M',
                'sp_date_list:[2021-01-01T00:00:00Z-7DAYS/DAY' .
                    ' TO 2021-01-01T00:00:00Z-2MONTHS/DAY]'
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
    #[DataProvider('additionProviderForFromIntervals')]
    public function testGetQueryWithFrom(
        string $from,
        string $expected
    ): void {
        $filter = new RelativeDateRangeFilter(
            null,
            new DateInterval($from),
            null,
        );

        $this->assertEquals(
            $expected,
            $filter->getQuery(),
            'unexpected query'
        );
    }

    /**
     * @throws Exception
     */
    #[DataProvider('additionProviderForToIntervals')]
    public function testGetQueryWithTo(
        string $to,
        string $expected
    ): void {
        $filter = new RelativeDateRangeFilter(
            null,
            null,
            new DateInterval($to),
        );

        $this->assertEquals(
            $expected,
            $filter->getQuery(),
            'unexpected query'
        );
    }

    /**
     * @throws Exception
     */
    #[DataProvider('additionProviderForFromAndToIntervals')]
    public function testGetQueryWithFromAndTo(
        string $from,
        string $to,
        string $expected
    ): void {
        $filter = new RelativeDateRangeFilter(
            null,
            new DateInterval($from),
            new DateInterval($to),
        );

        $this->assertEquals(
            $expected,
            $filter->getQuery(),
            'unexpected query'
        );
    }

    /**
     * @throws Exception
     */
    #[DataProvider('additionProviderWithBase')]
    public function testGetQueryWithBase(
        DateTime $base,
        ?string $from,
        ?string $to,
        string $expected
    ): void {
        $filter = new RelativeDateRangeFilter(
            $base,
            $from === null ? null : new DateInterval($from),
            $to === null ? null : new DateInterval($to),
        );

        $this->assertEquals(
            $expected,
            $filter->getQuery(),
            'unexpected query'
        );
    }

    /**
     * @throws Exception
     */
    #[DataProvider('additionProviderForInvalidIntervals')]
    public function testGetQueryWithInvalidIntervals(
        string $interval
    ): void {
        $filter = new RelativeDateRangeFilter(
            null,
            null,
            new DateInterval($interval),
        );
        $this->expectException(InvalidArgumentException::class);
        $filter->getQuery();
    }
}
