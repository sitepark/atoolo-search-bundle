<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

use Atoolo\Search\Dto\Search\Query\DateRangeRound;
use DateInterval;
use DateTime;
use InvalidArgumentException;

class RelativeDateRangeFilter extends Filter
{
    public function __construct(
        private readonly ?DateTime $base,
        private readonly ?DateInterval $before,
        private readonly ?DateInterval $after,
        private readonly ?DateRangeRound $roundStart,
        private readonly ?DateRangeRound $roundEnd,
        ?string $key = null
    ) {
        parent::__construct(
            $key,
            $key !== null ? [$key] : []
        );
    }

    public function getQuery(): string
    {
        return 'sp_date_list:' . $this->toSolrDateRage();
    }

    private function toSolrDateRage(): string
    {
        if ($this->before === null) {
            $from = $this->roundStart($this->getBaseInSolrSyntax());
        } else {
            $from = $this->roundStart(
                $this->toSolrIntervalSyntax($this->before, '-')
            );
        }

        if ($this->after === null) {
            $to = $this->roundEnd($this->getBaseInSolrSyntax());
        } else {
            $to = $this->roundEnd(
                $this->toSolrIntervalSyntax($this->after, '+')
            );
        }

        return '[' . $from . ' TO ' . $to . ']';
    }

    private function roundStart(string $start): string
    {
        return $start . $this->toSolrRound(
            $this->roundStart ?? DateRangeRound::START_OF_DAY
        );
    }

    private function roundEnd(string $start): string
    {
        return $start . $this->toSolrRound(
            $this->roundEnd ?? DateRangeRound::END_OF_DAY
        );
    }

    private function getBaseInSolrSyntax(): string
    {
        if ($this->base === null) {
            return 'NOW';
        }

        $formatter = clone $this->base;
        $formatter->setTimezone(new \DateTimeZone('UTC'));
        return $formatter->format('Y-m-d\TH:i:s\Z');
    }

    private function toSolrIntervalSyntax(
        DateInterval $value,
        string $operator
    ): string {
        $interval = $this->getBaseInSolrSyntax();
        if ($value->y > 0) {
            $interval = $interval . $operator . $value->y . 'YEARS';
        }
        if ($value->m > 0) {
            $interval = $interval . $operator . $value->m . 'MONTHS';
        }
        if ($value->d > 0) {
            $interval = $interval . $operator . $value->d . 'DAYS';
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

    private function toSolrRound(DateRangeRound $round): string
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
}
