<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

class NotFilter extends Filter
{
    public function __construct(
        ?string $key,
        private readonly Filter $filter,
        array $tags = []
    ) {
        parent::__construct($key, $tags);
    }

    public function getQuery(): string
    {
        return 'NOT ' . $this->filter->getQuery();
    }
}
