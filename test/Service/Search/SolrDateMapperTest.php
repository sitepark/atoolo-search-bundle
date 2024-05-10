<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Search\Dto\Search\Query\DateRangeRound;
use Atoolo\Search\Service\Search\SolrDateMapper;
use DateInterval;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SolrDateMapper::class)]
class SolrDateMapperTest extends TestCase
{
    /**
     * @return array<array{string, string, string}>
     */
    public static function getDateIntervals()
    {
        return [
            [ 'P1D', '+', '+1DAYS' ],
            [ 'P2W', '+', '+14DAYS' ],
            [ 'P3M', '+', '+3MONTHS' ],
            [ 'P2Y', '+', '+2YEARS' ],
            [ 'P1D', '-', '-1DAYS' ],
            [ 'P2W', '-', '-14DAYS' ],
            [ 'P3M', '-', '-3MONTHS' ],
            [ 'P2Y', '-', '-2YEARS' ],
            [ 'PT1H', '+', 'error' ],
            [ 'PT1M', '+', 'error' ],
            [ 'PT1S', '+', 'error' ],
        ];
    }

    /**
     * @return array<array{DateRangeRound, string}>
     */
    public static function getDateRangeRounds()
    {
        return [
            [ DateRangeRound::START_OF_DAY, '/DAY' ],
            [ DateRangeRound::START_OF_PREVIOUS_DAY, '/DAY-1DAY' ],
            [ DateRangeRound::END_OF_DAY, '/DAY+1DAY-1SECOND' ],
            [ DateRangeRound::END_OF_PREVIOUS_DAY, '/DAY-1SECOND' ],
            [ DateRangeRound::START_OF_MONTH, '/MONTH' ],
            [ DateRangeRound::START_OF_PREVIOUS_MONTH, '/MONTH-1MONTH' ],
            [ DateRangeRound::END_OF_MONTH, '/MONTH+1MONTH-1SECOND' ],
            [ DateRangeRound::END_OF_PREVIOUS_MONTH, '/MONTH-1SECOND' ],
            [ DateRangeRound::START_OF_YEAR, '/YEAR' ],
            [ DateRangeRound::START_OF_PREVIOUS_YEAR, '/YEAR-1YEAR' ],
            [ DateRangeRound::END_OF_YEAR, '/YEAR+1YEAR-1SECOND' ],
            [ DateRangeRound::END_OF_PREVIOUS_YEAR, '/YEAR-1SECOND' ],
        ];
    }

    #[DataProvider('getDateIntervals')]
    public function testMapDateInterval(
        string $interval,
        string $operator,
        string $expected
    ): void {

        if ($expected === 'error') {
            $this->expectException(InvalidArgumentException::class);
        }

        $result = SolrDateMapper::mapDateInterval(
            new DateInterval($interval),
            $operator
        );

        if ($expected !== 'error') {
            $this->assertEquals(
                $expected,
                $result,
                'Unexpected date interval mapping'
            );
        }
    }

    public function testMapDateIntervalWithDefault(): void
    {
        $this->assertEquals(
            '+1DAY',
            SolrDateMapper::mapDateInterval(null, '+'),
            'unexpected default date interval'
        );
    }

    #[DataProvider('getDateRangeRounds')]
    public function testMapDateRangeRound(
        DateRangeRound $round,
        string $expected
    ): void {
        $this->assertEquals(
            $expected,
            SolrDateMapper::mapDateRangeRound($round),
            'Unexpected date range round mapping'
        );
    }

    public function testMapDateTime(): void
    {
        $dateTime = new \DateTime('2021-01-01T00:00:00Z');
        $this->assertEquals(
            '2021-01-01T00:00:00Z',
            SolrDateMapper::mapDateTime($dateTime),
            'Unexpected date time mapping'
        );
    }

    public function testMapDateTimeWithNull(): void
    {
        $this->assertEquals(
            'NOW',
            SolrDateMapper::mapDateTime(null),
            'NOW should be returned for null date time'
        );
    }

    public function testMapDateTimeWithDefault(): void
    {
        $default = new \DateTime('2021-01-01T00:00:00Z');

        $this->assertEquals(
            '2021-01-01T00:00:00Z',
            SolrDateMapper::mapDateTime($default),
            'default date time should be returned'
        );
    }

    public function testRoundStart(): void
    {
        $this->assertEquals(
            '2021-01-01T00:00:00Z/DAY-1DAY',
            SolrDateMapper::roundStart(
                '2021-01-01T00:00:00Z',
                DateRangeRound::START_OF_PREVIOUS_DAY
            ),
            'Unexpected round date'
        );
    }

    public function testRoundStartWithDefault(): void
    {
        $this->assertEquals(
            '2021-01-01T00:00:00Z/DAY',
            SolrDateMapper::roundStart(
                '2021-01-01T00:00:00Z',
                null
            ),
            'Unexpected round date'
        );
    }

    public function testRoundEnd(): void
    {
        $this->assertEquals(
            '2021-01-01T00:00:00Z/MONTH-1SECOND',
            SolrDateMapper::roundEnd(
                '2021-01-01T00:00:00Z',
                DateRangeRound::END_OF_PREVIOUS_MONTH
            ),
            'Unexpected round date'
        );
    }

    public function testRoundEndWithDefault(): void
    {
        $this->assertEquals(
            '2021-01-01T00:00:00Z/DAY+1DAY-1SECOND',
            SolrDateMapper::roundEnd(
                '2021-01-01T00:00:00Z',
                null
            ),
            'Unexpected round date'
        );
    }
}
