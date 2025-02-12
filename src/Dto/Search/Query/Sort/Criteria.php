<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Sort;

use Symfony\Component\Serializer\Attribute\DiscriminatorMap;

/**
 * @codeCoverageIgnore
 */
#[DiscriminatorMap(
    typeProperty: 'type',
    mapping: [
        'customField' => CustomField::class,
        'date' => Date::class,
        'name' => Name::class,
        'natural' => Natural::class,
        'score' => Score::class,
        'spatialDist' => SpatialDist::class,
    ],
)]
abstract class Criteria
{
    public function __construct(
        public readonly Direction $direction = Direction::ASC,
    ) {}
}
