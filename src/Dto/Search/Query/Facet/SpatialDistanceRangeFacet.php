<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

use Atoolo\Search\Dto\Search\Query\GeoPoint;

/**
 * @codeCoverageIgnore
 */
class SpatialDistanceRangeFacet extends Facet
{
    public function __construct(
        string $key,
        public readonly GeoPoint $point,
        public readonly float $from,
        public readonly float $to,
        array $excludeFilter = [],
    ) {
        parent::__construct($key, $excludeFilter);
    }
}
