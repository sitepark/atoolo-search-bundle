<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

/**
 * @codeCoverageIgnore
 */
enum SpatialOrbitalMode: string
{
    case GREAT_CIRCLE_DISTANCE = 'great-circle-distance';
    case BOUNDING_BOX = 'bounding-box';
}
