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
    /**
     * @deprecated use property `$from` instead
     */
    public readonly ?DateInterval $before;

    /**
     * @deprecated use property `$to` instead
     */
    public readonly ?DateInterval $after;

    public readonly ?DateInterval $from;

    public readonly ?DateInterval $to;

    public function __construct(
        public readonly ?DateTime $base = null,
        ?DateInterval $before = null,
        ?DateInterval $after = null,
        public readonly ?DateRangeRound $roundStart = null,
        public readonly ?DateRangeRound $roundEnd = null,
        ?string $key = null,
        ?DateInterval $from = null,
        ?DateInterval $to = null,
    ) {
        if ($before !== null && $from !== null) {
            throw new \InvalidArgumentException(
                'Cannot use both the deprecated "before" and new "from" arguments. Please use only "from".',
            );
        }
        if ($after !== null && $to !== null) {
            throw new \InvalidArgumentException(
                'Cannot use both the deprecated "after" and new "to" arguments. Please use only "to".',
            );
        }
        $this->before = $before;
        $this->after = $after;
        $this->to = $to ?? $after;
        if ($from === null && $before !== null) {
            $this->from = clone $before;
            $this->from->invert = $this->from->invert === 1 ? 0 : 1;
        } else {
            $this->from = $from;
        }
        parent::__construct(
            $key,
            $key !== null ? [$key] : [],
        );
    }
}
