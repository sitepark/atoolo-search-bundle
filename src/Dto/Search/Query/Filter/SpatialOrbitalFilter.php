<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

use Atoolo\Search\Dto\Search\Query\GeoPoint;

/**
 * @codeCoverageIgnore
 */
class SpatialOrbitalFilter extends Filter
{
    public function __construct(
        /** The radial distance in kilometers */
        public readonly float $distance,
        public readonly GeoPoint $centerPoint,
        public readonly SpatialOrbitalMode $mode = SpatialOrbitalMode::GREAT_CIRCLE_DISTANCE,
        ?string $key = null,
    ) {
        parent::__construct($key);
    }
}
