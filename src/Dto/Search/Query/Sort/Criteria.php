<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Sort;

abstract class Criteria
{
    public function __construct(
        public readonly Direction $direction
    ) {
    }
}
