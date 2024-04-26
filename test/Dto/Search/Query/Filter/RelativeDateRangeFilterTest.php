<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Dto\Search\Query\Filter;

use Atoolo\Search\Dto\Search\Query\Filter\RelativeDateRangeFilter;
use DateInterval;
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
    #[DataProvider('additionProviderForInvalidIntervals')]
    public function testGetQueryWithInvalidIntervals(
        string $interval
    ): void {
        $filter = new RelativeDateRangeFilter(
            null,
            new DateInterval($interval),
        );
        $this->expectException(InvalidArgumentException::class);
        $filter->getQuery();
    }
}
