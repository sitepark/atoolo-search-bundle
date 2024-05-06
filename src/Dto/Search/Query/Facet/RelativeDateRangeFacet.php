<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

use Atoolo\Search\Dto\Search\Query\DateRangeRound;
use DateInterval;

/**
 * @codeCoverageIgnore
 */
class RelativeDateRangeFacet extends Facet
{
    /**
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        public readonly ?\DateTime $base,
        public readonly ?DateInterval $before,
        public readonly ?DateInterval $after,
        public readonly ?DateInterval $gap,
        public readonly ?DateRangeRound $roundStart,
        public readonly ?DateRangeRound $roundEnd,
        array $excludeFilter = []
    ) {
        parent::__construct(
            $key,
            $excludeFilter
        );
    }
}
