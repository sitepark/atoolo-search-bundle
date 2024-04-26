<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

use DateInterval;
use InvalidArgumentException;

class RelativeDateRangeFilter extends Filter
{
    public function __construct(
        private readonly ?DateInterval $from,
        private readonly ?DateInterval $to,
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
        if ($this->from === null) {
            $from = "NOW/DAY";
        } else {
            $from = $this->toSolrIntervalSyntax($this->from);
        }

        if ($this->to === null) {
            $to = "NOW/DAY+1DAY-1SECOND";
        } else {
            $to = $this->toSolrIntervalSyntax($this->to);
        }

        return '[' . $from . ' TO ' . $to . ']';
    }

    private function toSolrIntervalSyntax(DateInterval $value): string
    {
        $interval = 'NOW';
        if ($value->y > 0) {
            $interval = $interval . '-' . $value->y . 'YEARS';
        }
        if ($value->m > 0) {
            $interval = $interval . '-' . $value->m . 'MONTHS';
        }
        if ($value->d > 0) {
            $interval = $interval . '-' . $value->d . 'DAYS';
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

        return $interval . '/DAY';
    }
}
