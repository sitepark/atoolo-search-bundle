<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

/**
 * @codeCoverageIgnore
 */
class SolrXmlIndexerParameter
{
    /**
     * @param array<string> $xmlFileList
     */
    public function __construct(
        public readonly string $source,
        public readonly int $cleanupThreshold,
        public readonly int $chunkSize,
        public readonly array $xmlFileList,
    ) {}
}
