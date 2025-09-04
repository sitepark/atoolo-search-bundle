<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

use Atoolo\Search\Dto\Search\Query\DateRangeRound;
use Atoolo\Search\Dto\Search\Query\DirectedDateInterval;
use DateInterval;

/**
 * @codeCoverageIgnore
 */
class RelativeDateRangeFacet extends Facet
{
    /**
     * @param DateInterval|DirectedDateInterval|null $before
     * @param DateInterval|DirectedDateInterval|null $after
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        public readonly ?\DateTime $base = null,
        public readonly mixed $before = null,
        public readonly mixed $after = null,
        public readonly ?DateInterval $gap = null,
        public readonly ?DateRangeRound $roundStart = null,
        public readonly ?DateRangeRound $roundEnd = null,
        array $excludeFilter = [],
    ) {
        parent::__construct(
            $key,
            $excludeFilter,
        );
    }
}
