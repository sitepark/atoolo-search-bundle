<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

class Filter
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        private readonly string $key,
        private readonly string $query,
        private readonly array $tags = []
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
