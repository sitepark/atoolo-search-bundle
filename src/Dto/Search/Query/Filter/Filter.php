<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

abstract class Filter
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        private readonly ?string $key,
        private readonly array $tags = []
    ) {
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    abstract public function getQuery(): string;

    /**
     * @return string[]
     */
    public function getTags(): array
    {
        return $this->tags;
    }
}
