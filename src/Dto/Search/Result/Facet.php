<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Result;

class Facet
{
    public function __construct(
        private readonly string $key,
        private readonly int $hits
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getHits(): int
    {
        return $this->hits;
    }
}