<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

use DateTime;
use InvalidArgumentException;

class AbsoluteDateRangeFilter extends Filter
{
    public function __construct(
        public readonly ?DateTime $from,
        public readonly ?DateTime $to,
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
}
