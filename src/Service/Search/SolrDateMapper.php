<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\DateRangeRound;
use DateInterval;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;

class SolrDateMapper
{
    public static function mapDateInterval(
        ?DateInterval $value,
        string $operator
    ): string {
        if ($value === null) {
            return $operator . '1DAY';
        }

        $interval = '';
        if ($value->y > 0) {
            $interval = $operator . $value->y . 'YEARS';
        }
        if ($value->m > 0) {
            $interval = $operator . $value->m . 'MONTHS';
        }
        if ($value->d > 0) {
            $interval = $operator . $value->d . 'DAYS';
        }
        if ($value->h > 0) {
            throw new InvalidArgumentException(
                'Hours are not supported for the RelativeDateRangeFilter'
            );
        }
        if ($value->i > 0) {
            throw new InvalidArgumentException(
                'Minutes are not supported for the RelativeDateRangeFilter'
            );
        }
        if ($value->s > 0) {
            throw new InvalidArgumentException(
                'Seconds are not supported for the RelativeDateRangeFilter'
            );
        }
        return $interval;
    }

    public static function mapDateTime(
        ?DateTime $date,
        string $default = 'NOW'
    ): string {
        if ($date === null) {
            return $default;
        }
        $formatter = clone $date;
        $formatter->setTimezone(new DateTimeZone('UTC'));
        return $formatter->format('Y-m-d\TH:i:s\Z');
    }

    public static function mapDateRangeRound(DateRangeRound $round): string
    {
        if ($round === DateRangeRound::START_OF_DAY) {
            return '/DAY';
        }
        if ($round === DateRangeRound::START_OF_PREVIOUS_DAY) {
            return '/DAY-1DAY';
        }
        if ($round === DateRangeRound::END_OF_DAY) {
            return '/DAY+1DAY-1SECOND';
        }
        if ($round === DateRangeRound::END_OF_PREVIOUS_DAY) {
            return '/DAY-1SECOND';
        }
        if ($round === DateRangeRound::START_OF_MONTH) {
            return '/MONTH';
        }
        if ($round === DateRangeRound::START_OF_PREVIOUS_MONTH) {
            return '/MONTH-1MONTH';
        }
        if ($round === DateRangeRound::END_OF_MONTH) {
            return '/MONTH+1MONTH-1SECOND';
        }
        if ($round === DateRangeRound::END_OF_PREVIOUS_MONTH) {
            return '/MONTH-1SECOND';
        }
        if ($round === DateRangeRound::START_OF_YEAR) {
            return '/YEAR';
        }
        if ($round === DateRangeRound::START_OF_PREVIOUS_YEAR) {
            return '/YEAR-1YEAR';
        }
        if ($round === DateRangeRound::END_OF_YEAR) {
            return '/YEAR+1YEAR-1SECOND';
        }
        if ($round === DateRangeRound::END_OF_PREVIOUS_YEAR) {
            return '/YEAR-1SECOND';
        }
    }

    public static function roundStart(
        string $start,
        ?DateRangeRound $round
    ): string {
        return $start . self::mapDateRangeRound(
            $round ?? DateRangeRound::START_OF_DAY
        );
    }

    public static function roundEnd(
        string $end,
        ?DateRangeRound $round
    ): string {
        return $end . self::mapDateRangeRound(
            $round ?? DateRangeRound::END_OF_DAY
        );
    }
}
