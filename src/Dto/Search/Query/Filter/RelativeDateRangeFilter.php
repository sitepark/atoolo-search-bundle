<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

use Atoolo\Search\Dto\Search\Query\DateRangeRound;
use DateInterval;
use DateTime;

/**
 * @codeCoverageIgnore
 */
class RelativeDateRangeFilter extends Filter
{
    public function __construct(
        public readonly ?DateTime $base,
        public readonly ?DateInterval $before,
        public readonly ?DateInterval $after,
        public readonly ?DateRangeRound $roundStart,
        public readonly ?DateRangeRound $roundEnd,
        ?string $key = null,
    ) {
        parent::__construct(
            $key,
            $key !== null ? [$key] : [],
        );
    }
}
