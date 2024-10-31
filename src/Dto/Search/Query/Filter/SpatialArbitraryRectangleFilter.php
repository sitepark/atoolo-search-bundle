<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

use Atoolo\Search\Dto\Search\Query\GeoPoint;

/**
 * @codeCoverageIgnore
 */
class SpatialArbitraryRectangleFilter extends Filter
{
    public function __construct(
        public readonly GeoPoint $lowerLeftCorner,
        public readonly GeoPoint $upperRightCorner,
        ?string $key = null,
    ) {
        parent::__construct($key);
    }
}
