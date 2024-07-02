<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console\Command\Io;

use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use Exception;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * @phpcs:disable Generic.Files.LineLength
 */
#[CoversClass(IndexerProgressBar::class)]
class IndexerProgressBarTest extends TestCase
{
    private IndexerProgressBar $progressBar;

    private IndexerProgressHandler&MockObject $progressHandler;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function setUp(): void
    {
        $output = $this->createMock(OutputInterface::class);
        $this->progressHandler = $this->createMock(IndexerProgressHandler::class);
        $this->progressBar = new IndexerProgressBar($output);
        $this->progressBar->init($this->progressHandler);
    }

    public function testPrepare(): void
    {
        $this->progressHandler
            ->expects($this->once())
            ->method('prepare')
            ->with('test');
        $this->progressBar->prepare('test');
    }

    public function testStart(): void
    {
        $this->progressHandler
            ->expects($this->once())
            ->method('start')
            ->with(10);
        $this->progressBar->start(10);
    }

    public function testStartAfterPrepare(): void
    {
        $this->progressBar->prepare('test');

        $this->progressHandler
            ->expects($this->once())
            ->method('start')
            ->with(10);
        $this->progressBar->start(10);
    }

    /**
     * @throws ExceptionInterface
     */
    public function testStartUpdate(): void
    {
        $this->progressHandler
            ->expects($this->once())
            ->method('startUpdate')
            ->with(10);
        $this->progressBar->startUpdate(10);
    }

    /**
     * @phpcs:disable Generic.Files.LineLength
     * @throws JsonException
     */
    public function testAdvance(): void
    {

        $this->progressBar->start(10);

        $this->progressHandler
            ->expects($this->once())
            ->method('advance')
            ->with(1);

        $this->progressBar->advance(1);
    }

    public function testSkip(): void
    {
        $this->progressBar->start(10);

        $this->progressHandler
            ->expects($this->once())
            ->method('skip')
            ->with(10);

        $this->progressBar->skip(10);
    }

    public function testError(): void
    {

        $this->progressBar->start(10);

        $e = new Exception('test');

        $this->progressHandler
            ->expects($this->once())
            ->method('error')
            ->with($e);

        $this->progressBar->error($e);
    }

    public function testGetError(): void
    {
        $this->progressBar->start(10);

        $e = new Exception('test');
        $this->progressBar->error($e);

        $this->assertCount(
            1,
            $this->progressBar->getErrors(),
            'unexpected error count',
        );
    }

    /**
     * @throws JsonException
     */
    public function testFinish(): void
    {

        $this->progressBar->start(10);

        $this->progressHandler
            ->expects($this->once())
            ->method('finish');
        $this->progressBar->finish();
    }

    public function testAbort(): void
    {
        $this->progressBar->start(10);

        $this->progressHandler
            ->expects($this->once())
            ->method('abort');
        $this->progressBar->abort();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testGetStatus(): void
    {
        $status = $this->createStub(IndexerStatus::class);
        $this->progressHandler->method('getStatus')->willReturn($status);
        $this->progressHandler
            ->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);
        $this->progressBar->getStatus();
    }
}
