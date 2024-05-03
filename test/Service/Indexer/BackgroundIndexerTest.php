<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Search\Service\Indexer\BackgroundIndexer;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
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
    private InternalResourceIndexer&MockObject $internalResourceIndexer;
    private IndexerStatusStore&MockObject $statusStore;
    private IndexerProgressHandler $progressHandler;
    private BackgroundIndexer $indexer;

    public function setUp(): void
    {

        $this->progressHandler = $this->createStub(
            IndexerProgressHandler::class
        );
        $this->indexName = $this->createMock(IndexName::class);
        $this->internalResourceIndexer = $this->createMock(
            InternalResourceIndexer::class
        );
        $this->internalResourceIndexer->method('getSource')
            ->willReturn('internal');
        $this->internalResourceIndexer->method('getProgressHandler')
            ->willReturn($this->progressHandler);
        $this->statusStore = $this->createMock(IndexerStatusStore::class);
        $this->indexer = new BackgroundIndexer(
            $this->internalResourceIndexer,
            $this->indexName,
            $this->statusStore
        );
    }

    public function testRemove(): void
    {
        $this->internalResourceIndexer->expects($this->once())
            ->method('remove');
        $this->indexer->remove(['123']);
    }

    public function testAbort(): void
    {
        $this->internalResourceIndexer->expects($this->once())
            ->method('abort');
        $this->indexer->abort();
    }

    public function testIndex(): void
    {
        $this->internalResourceIndexer->expects($this->once())
            ->method('index');
        $this->indexer->index();
    }

    public function testUpdate(): void
    {
        $this->internalResourceIndexer->expects($this->once())
            ->method('update');
        $this->indexer->update(['/index.php']);
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

        $this->internalResourceIndexer->expects($this->exactly(0))
            ->method('index');

        $indexer->index();
    }

    public function testGetStatus(): void
    {
        $this->statusStore->expects($this->once())
            ->method('load');
        $this->indexer->getStatus();
    }

    public function testEnable(): void
    {
        $this->assertTrue(
            $this->indexer->enabled(),
            'indexer should always be enabled'
        );
    }

    public function testGetSource(): void
    {
        $this->assertEquals(
            'internal',
            $this->indexer->getSource(),
            'Unexpected source'
        );
    }

    public function testGetProgressHandler(): void
    {
        $this->internalResourceIndexer->expects($this->once())
            ->method('getProgressHandler');
        $this->indexer->getProgressHandler();
    }

    public function testSetProgressHandler(): void
    {
        $progressHandler = $this->createStub(IndexerProgressHandler::class);
        $this->internalResourceIndexer->expects($this->once())
            ->method('setProgressHandler');
        $this->indexer->setProgressHandler($progressHandler);
    }

    public function testGetName(): void
    {
        $this->assertEquals(
            'Background Indexer',
            $this->indexer->getName(),
            'Unexpected name'
        );
    }
}
