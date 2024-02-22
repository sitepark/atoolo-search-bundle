<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

/**
 * @codeCoverageIgnore
 */
class ObjectTypeFilter extends FieldFilter
{
    public function __construct(
        ?string $key,
        string ...$objectTypes
    ) {
        parent::__construct(
            $key,
            'sp_objecttype',
            ...$objectTypes
        );
    }
}
