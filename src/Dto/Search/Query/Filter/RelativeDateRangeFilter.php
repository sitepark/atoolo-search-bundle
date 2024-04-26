<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

use DateInterval;
use InvalidArgumentException;

class RelativeDateRangeFilter extends Filter
{
    public function __construct(
        private readonly ?\DateTime $base,
        private readonly ?DateInterval $before,
        private readonly ?DateInterval $after,
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
            $from = $this->getBaseInSolrSyntax() . "/DAY";
        } else {
            $from = $this->toSolrIntervalSyntax($this->before, '-') .
                '/DAY';
        }

        if ($this->after === null) {
            $to = $this->getBaseInSolrSyntax() . "/DAY+1DAY-1SECOND";
        } else {
            $to = $this->toSolrIntervalSyntax($this->after, '+') .
                "/DAY+1DAY-1SECOND";
        }

        return '[' . $from . ' TO ' . $to . ']';
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

    private function toSolrIntervalSyntax(DateInterval $value, string $operator): string
    {
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
}
