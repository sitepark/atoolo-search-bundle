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
     * @deprecated use property `$from` instead
     */
    public readonly ?DateInterval $before;

    /**
     * @deprecated use property `$to` instead
     */
    public readonly ?DateInterval $after;

    /**
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        public readonly ?\DateTime $base = null,
        ?DateInterval $before = null,
        ?DateInterval $after = null,
        public readonly ?DateInterval $gap = null,
        public readonly ?DateRangeRound $roundStart = null,
        public readonly ?DateRangeRound $roundEnd = null,
        array $excludeFilter = [],
        public readonly ?DateInterval $from = null,
        public readonly ?DateInterval $to = null,
    ) {
        $this->before = $before;
        $this->after = $after;
        if ($this->before !== null && $this->from !== null) {
            throw new \InvalidArgumentException(
                'Cannot use both the deprecated "before" and new "from" arguments. Please use only "from".'
            );
        }
        if ($this->after !== null && $this->to !== null) {
            throw new \InvalidArgumentException(
                'Cannot use both the deprecated "after" and new "to" arguments. Please use only "to".'
            );
        }
        parent::__construct(
            $key,
            $excludeFilter,
        );
    }
}
