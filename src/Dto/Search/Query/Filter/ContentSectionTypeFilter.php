<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

class ContentSectionTypeFilter extends FieldFilter
{
    public function __construct(string $key, string ...$contentTypes)
    {
        parent::__construct(
            $key,
            'sp_contenttype',
            ...$contentTypes
        );
    }
}
