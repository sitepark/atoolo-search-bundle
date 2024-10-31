<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Sort;

use Atoolo\Search\Dto\Search\Query\GeoPoint;

/**
 * @codeCoverageIgnore
 */
class SpatialDist extends Criteria
{
    public function __construct(
        Direction $direction,
        public readonly GeoPoint $point,
    ) {
        parent::__construct($direction);
    }
}
