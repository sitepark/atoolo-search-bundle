<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use ArrayIterator;
use Atoolo\Search\Indexer;
use Atoolo\Search\Service\Indexer\IndexerCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexerCollection::class)]
class IndexerCollectionTest extends TestCase
{
    public function testGetIndexer(): void
    {
        $indexer = $this->createStub(Indexer::class);
        $indexer->method('getSource')->willReturn('test');
        $indexers = new IndexerCollection([$indexer]);
        $this->assertNotNull($indexers->getIndexer('test'));
    }

    public function testGetMissingIndexer(): void
    {
        $indexers = new IndexerCollection([]);
        $this->expectException(\InvalidArgumentException::class);
        $indexers->getIndexer('test');
    }

    public function testGetIndexers(): void
    {
        $indexer = $this->createStub(Indexer::class);
        $indexers = new IndexerCollection([$indexer]);
        $this->assertCount(
            1,
            $indexers->getIndexers(),
            'unexpected number of indexers',
        );
    }

    public function testGetIndexersWithIterable(): void
    {
        $indexer = $this->createStub(Indexer::class);
        $indexers = new IndexerCollection(new ArrayIterator([$indexer]));
        $this->assertCount(
            1,
            $indexers->getIndexers(),
            'unexpected number of indexers',
        );
    }
}
