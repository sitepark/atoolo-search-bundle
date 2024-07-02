<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Search\Indexer;
use InvalidArgumentException;

class IndexerCollection
{
    /**
     * @param iterable<Indexer> $indexers
     */
    public function __construct(
        private readonly iterable $indexers,
    ) {}

    public function getIndexer(string $source): Indexer
    {
        foreach ($this->indexers as $indexer) {
            if ($indexer->getSource() === $source) {
                return $indexer;
            }
        }
        throw new InvalidArgumentException(
            'Indexer not found for source: ' . $source,
        );
    }

    /**
     * @return array<Indexer>
     */
    public function getIndexers(): array
    {
        return $this->indexers instanceof \Traversable
            ? iterator_to_array($this->indexers)
            : $this->indexers;
    }
}
