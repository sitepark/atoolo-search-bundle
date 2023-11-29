<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

class GroupFilter extends FieldFilter
{
    public function __construct(string $key, string ...$group)
    {
        parent::__construct(
            $key,
            'sp_group_path',
            ...$group
        );
    }
}
