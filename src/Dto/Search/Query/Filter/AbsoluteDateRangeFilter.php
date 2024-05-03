<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

use DateTime;
use InvalidArgumentException;

class AbsoluteDateRangeFilter extends Filter
{
    public function __construct(
        private readonly ?DateTime $from,
        private readonly ?DateTime $to,
        ?string $key = null
    ) {
        parent::__construct(
            $key,
            $key !== null ? [$key] : []
        );
        if ($this->from === null && $this->to === null) {
            throw new InvalidArgumentException(
                'At least `from` or `to` must be specified'
            );
        }
    }

    public function getQuery(): string
    {
        return 'sp_date_list:' .
            '[' .
            $this->formatDate($this->from) .
            ' TO ' .
            $this->formatDate($this->to) .
            ']';
    }

    private function formatDate(?DateTime $date): string
    {
        if ($date === null) {
            return '*';
        }

        $formatter = clone $date;
        $formatter->setTimezone(new \DateTimeZone('UTC'));
        return $formatter->format('Y-m-d\TH:i:s\Z');
    }
}
