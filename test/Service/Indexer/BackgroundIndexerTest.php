<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Search\Dto\Indexer\IndexerParameter;
use Atoolo\Search\Service\Indexer\BackgroundIndexer;
use Atoolo\Search\Service\Indexer\IndexerStatusStore;
use Atoolo\Search\Service\Indexer\InternalResourceIndexer;
use Atoolo\Search\Service\IndexName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\SharedLockInterface;

#[CoversClass(BackgroundIndexer::class)]
class BackgroundIndexerTest extends TestCase
{
    private IndexName $indexName;
    private InternalResourceIndexer&MockObject $solrIndexer;
    private IndexerStatusStore&MockObject $statusStore;
    private BackgroundIndexer $indexer;

    public function setUp(): void
    {

        $this->indexName = $this->createMock(IndexName::class);
        $this->solrIndexer = $this->createMock(InternalResourceIndexer::class);
        $this->statusStore = $this->createMock(IndexerStatusStore::class);
        $this->indexer = new BackgroundIndexer(
            $this->solrIndexer,
            $this->indexName,
            $this->statusStore
        );
    }

    public function testRemove(): void
    {
        $this->solrIndexer->expects($this->once())
            ->method('remove');
        $this->indexer->remove(['123']);
    }

    public function testAbort(): void
    {
        $this->solrIndexer->expects($this->once())
            ->method('abort');
        $this->indexer->abort();
    }

    public function testIndex(): void
    {
        $params = new IndexerParameter('');
        $this->solrIndexer->expects($this->once())
            ->method('index');
        $this->indexer->index($params);
    }

    public function testIndexIfLocked(): void
    {
        $lockFactory = $this->createStub(LockFactory::class);
        $indexer = new BackgroundIndexer(
            $this->createStub(InternalResourceIndexer::class),
            $this->createStub(IndexName::class),
            $this->statusStore
        );

        $lock = $this->createStub(SharedLockInterface::class);
        $lock->method('acquire')
            ->willReturn(false);
        $lockFactory->method('createLock')
            ->willReturn($lock);

        $this->solrIndexer->expects($this->exactly(0))
            ->method('index');

        $params = new IndexerParameter('');
        $indexer->index($params);
    }

    public function testGetStatus(): void
    {
        $this->statusStore->expects($this->once())
            ->method('load');
        $this->indexer->getStatus();
    }
}
