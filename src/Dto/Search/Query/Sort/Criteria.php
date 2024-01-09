<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Sort;

abstract class Criteria
{
    public function __construct(
        private readonly Direction $direction
    ) {
    }

    public function getDirection(): Direction
    {
        return $this->direction;
    }
}
