<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

use Atoolo\Search\Dto\Search\Query\Filter\Filter;

class AndFilter extends Filter
{
    /**
     * @param Filter[] $filter
     */
    public function __construct(
        ?string $key,
        private readonly array $filter,
        array $tags = []
    ) {
        parent::__construct($key, $tags);
    }

    public function getQuery(): string
    {
        $query = [];
        foreach ($this->filter as $filter) {
            $query[] = $filter->getQuery();
        }

        return '(' . implode(' AND ', $query) . ')';
    }
}
