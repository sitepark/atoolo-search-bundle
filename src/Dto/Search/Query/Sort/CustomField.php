<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Sort;

/**
 * @codeCoverageIgnore
 */
class CustomField extends Criteria
{
    public function __construct(
        public readonly string $field,
        Direction $direction = Direction::ASC,
    ) {
        parent::__construct($direction);
    }
}
