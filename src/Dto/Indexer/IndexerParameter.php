<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

class IndexerParameter
{
    public function __construct(
        public readonly string $coreId,
        public readonly string $basePath,
        public readonly int $cleanupThreshold = 0,
        public readonly array $directories = []
    ) {
    }
}
