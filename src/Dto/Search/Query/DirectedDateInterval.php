<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

use DateInterval;

/**
 * @codeCoverageIgnore
 */
class DirectedDateInterval
{
    public function __construct(
        public readonly DateInterval $interval,
        public readonly DateIntervalDirection $direction = DateIntervalDirection::FUTURE,
    ) {}
}
