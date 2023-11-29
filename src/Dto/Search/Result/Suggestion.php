<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Result;

class Suggestion
{
    public function __construct(
        private readonly string $term,
        private readonly int $hits
    ) {
    }

    public function getTerm(): string
    {
        return $this->term;
    }

    public function getHits(): int
    {
        return $this->hits;
    }
}
