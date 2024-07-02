<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

use InvalidArgumentException;

class FieldFilter extends Filter
{
    /**
     * @param string[] $values
     */
    public function __construct(
        public readonly array $values,
        ?string $key = null,
    ) {
        if (count($values) === 0) {
            throw new InvalidArgumentException(
                'values is an empty array',
            );
        }
        parent::__construct(
            $key,
            $key !== null ? [$key] : [],
        );
    }
}
