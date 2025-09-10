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

    public function __construct(
        public readonly ?DateTime $base = null,
        ?DateInterval $before = null,
        ?DateInterval $after = null,
        public readonly ?DateRangeRound $roundStart = null,
        public readonly ?DateRangeRound $roundEnd = null,
        ?string $key = null,
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
            $key !== null ? [$key] : [],
        );
    }
}
