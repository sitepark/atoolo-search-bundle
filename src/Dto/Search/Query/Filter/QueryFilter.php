<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

/**
 * @codeCoverageIgnore
 */
class QueryFilter extends Filter
{
    public function __construct(
        public readonly string $query,
        ?string $key = null,
    ) {
        parent::__construct(
            $key,
        );
    }
}
