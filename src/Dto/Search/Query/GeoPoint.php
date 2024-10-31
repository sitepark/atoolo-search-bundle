<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

/**
 * @codeCoverageIgnore
 */
class GeoPoint
{
    public function __construct(
        public readonly float $lng,
        public readonly float $lat,
    ) {}
}
