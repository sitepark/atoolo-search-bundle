<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service;

use Atoolo\Resource\DataBag;
use Atoolo\Search\Dto\Indexer\IndexerConfiguration;
use Atoolo\Search\Indexer;
use Atoolo\Search\Service\AbstractIndexer;
use Atoolo\Search\Service\Indexer\IndexerConfigurationLoader;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use Atoolo\Search\Service\Indexer\IndexingAborter;
use Atoolo\Search\Service\IndexName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractIndexer::class)]
class AbstractIndexerTest extends TestCase
{
    private Indexer $indexer;

    private IndexerProgressHandler $progressHandler;

    private IndexingAborter&MockObject $aborter;

    private IndexerConfigurationLoader&MockObject $configLoader;

    public function setUp(): void
    {
        $indexName = $this->createMock(IndexName::class);
        $indexName->method('name')
            ->willReturn('www');
        $this->progressHandler = $this->createMock(
            IndexerProgressHandler::class,
        );
        $this->aborter = $this->createMock(IndexingAborter::class);
        $config = new IndexerConfiguration(
            'test',
            'Test',
            new DataBag([]),
        );
        $this->configLoader = $this->createMock(
            IndexerConfigurationLoader::class,
        );
        $this->configLoader->method('load')
            ->willReturn($config);
        $this->indexer = new TextIndexer(
            $indexName,
            $this->progressHandler,
            $this->aborter,
            $this->configLoader,
            'test',
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals(
            'Test',
            $this->indexer->getName(),
            'The name of the indexer should be "Test"',
        );
    }

    public function testGetSource(): void
    {
        $this->assertEquals(
            'test',
            $this->indexer->getSource(),
            'The source of the indexer should be "test"',
        );
    }

    public function testGetProgressHandler(): void
    {
        $this->assertEquals(
            $this->progressHandler,
            $this->indexer->getProgressHandler(),
            'The progress handler should be the ' .
            'same as the one passed to the constructor',
        );
    }

    public function testSetProgressHandler(): void
    {
        $progressHandler = $this->createStub(IndexerProgressHandler::class);
        $this->indexer->setProgressHandler($progressHandler);

        $this->assertEquals(
            $progressHandler,
            $this->indexer->getProgressHandler(),
            'The progress handler should be the ' .
            'same as the one passed to the setProgressHandler method',
        );
    }

    public function testAbort(): void
    {

        $this->aborter->expects($this->once())
            ->method('requestAbortion')
            ->with('www-test');
        $this->indexer->abort();
    }

    public function testEnabled(): void
    {

        $this->configLoader->expects($this->once())
            ->method('exists')
            ->with('test');
        $this->indexer->enabled();
    }

    public function testIsAbortionRequested(): void
    {

        $this->aborter->expects($this->once())
            ->method('isAbortionRequested')
            ->with('www-test');
        $this->indexer->isAbortionRequested();
    }
}
