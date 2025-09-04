<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

use Atoolo\Search\Dto\Search\Query\DateRangeRound;
use Atoolo\Search\Dto\Search\Query\DirectedDateInterval;
use DateInterval;
use DateTime;

/**
 * @codeCoverageIgnore
 */
class RelativeDateRangeFilter extends Filter
{
    /**
     * @param DateInterval|DirectedDateInterval|null $before
     * @param DateInterval|DirectedDateInterval|null $after
     */
    public function __construct(
        public readonly ?DateTime $base = null,
        public readonly mixed $before = null,
        public readonly mixed $after = null,
        public readonly ?DateRangeRound $roundStart = null,
        public readonly ?DateRangeRound $roundEnd = null,
        ?string $key = null,
    ) {
        parent::__construct(
            $key,
            $key !== null ? [$key] : [],
        );
    }
}
