<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

class IndexerParameter
{
    public function __construct(
        public readonly string $name,
        public readonly int $cleanupThreshold = 0,
        public readonly int $chunkSize = 500
    ) {
        if ($this->chunkSize < 10) {
            throw new \InvalidArgumentException(
                'chunk size must be greater than 9'
            );
        }
    }
}
