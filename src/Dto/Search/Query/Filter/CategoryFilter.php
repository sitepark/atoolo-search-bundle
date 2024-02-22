<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

/**
 * @codeCoverageIgnore
 */
class CategoryFilter extends FieldFilter
{
    public function __construct(
        ?string $key,
        string ...$category
    ) {
        parent::__construct(
            $key,
            'sp_category_path',
            ...$category
        );
    }
}
