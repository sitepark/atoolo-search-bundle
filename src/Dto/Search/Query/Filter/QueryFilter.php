<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

class QueryFilter extends Filter
{
    public function __construct(
        ?string $key,
        private readonly string $query
    ) {
        parent::__construct(
            $key
        );
    }

    public function getQuery(): string
    {
        return $this->query;
    }
}
