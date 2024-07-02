<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

class IndexerParameter
{
    /**
     * @param array<string> $excludes
     */
    public function __construct(
        public readonly string $name,
        public readonly int $cleanupThreshold = 0,
        public readonly int $chunkSize = 500,
        public readonly array $excludes = [],
    ) {
        if ($this->chunkSize < 10) {
            throw new \InvalidArgumentException(
                'chunk size must be greater than 9',
            );
        }
    }
}
