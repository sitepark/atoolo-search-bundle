<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Dto\Indexer\IndexerStatusState;
use Atoolo\Search\Service\Indexer\IndexerProgressState;
use Atoolo\Search\Service\Indexer\IndexerStatusStore;
use Atoolo\Search\Service\IndexName;
use Exception;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexerProgressState::class)]
class IndexerProgressStateTest extends TestCase
{
    private IndexerStatusStore&MockObject $statusStore;
    private IndexerProgressState $state;

    private ?IndexerStatus $status = null;

    public function setUp(): void
    {
        $indexName = $this->createMock(IndexName::class);
        $indexName->method('name')->willReturn('test');

        $this->status = IndexerStatus::empty();

        $this->statusStore = $this->createMock(IndexerStatusStore::class);
        $that = $this;
        $this->statusStore->method('load')
            ->willReturnCallback(function () use ($that) {
                return $that->status;
            });
        $this->state = new IndexerProgressState(
            $indexName,
            $this->statusStore,
            'source'
        );
    }

    public function testPrepare(): void
    {
        $this->state->prepare('prepare message');

        $this->assertMatchesRegularExpression(
            '/\[PREPARING].*prepare message.*/',
            $this->state->getStatus()->getStatusLine(),
            "unexpected status line"
        );
    }

    public function testStart(): void
    {
        $this->state->start(10);

        $this->assertMatchesRegularExpression(
            '/\[RUNNING].*processed: 0\/10,/',
            $this->state->getStatus()->getStatusLine(),
            "unexpected status line"
        );
    }

    public function testStartAfterPrepare(): void
    {

        $startTime = new \DateTime();
        $startTime->setDate(2024, 1, 31);
        $startTime->setTime(11, 15, 10);

        $endTime = new \DateTime();
        $endTime->setDate(2024, 1, 31);
        $endTime->setTime(12, 16, 11);

        $this->status = new IndexerStatus(
            IndexerStatusState::PREPARING,
            $startTime,
            $endTime,
            0,
            0,
            0,
            $endTime,
            0,
            0,
            'prepare message'
        );

        $this->state->start(10);

        $this->assertMatchesRegularExpression(
            '/\[RUNNING].*start: 31\.01\.2024 11:15,.*processed: 0\/10,/',
            $this->state->getStatus()->getStatusLine(),
            "unexpected status line"
        );
    }

    public function testUpdate(): void
    {

        $this->statusStore
            ->method('load')
            ->willReturn(IndexerStatus::empty());

        $this->state->startUpdate(10);

        $this->assertMatchesRegularExpression(
            '/\[RUNNING].*processed: 0\/10,/',
            $this->state->getStatus()->getStatusLine(),
            "unexpected status line"
        );
    }

    public function testAdvanceWithoutStart(): void
    {
        $this->expectException(LogicException::class);
        $this->state->advance(1);
    }

    public function testAdvanceForUpdate(): void
    {

        $this->statusStore
            ->method('load')
            ->willReturn(IndexerStatus::empty());

        $this->state->startUpdate(10);

        $this->statusStore->expects($this->once())
            ->method('store');

        $this->state->advance(1);

        $this->assertMatchesRegularExpression(
            '/\[RUNNING].*processed: 1\/10,.*updated: 1,/',
            $this->state->getStatus()->getStatusLine(),
            "unexpected status line"
        );
    }

    public function testSkip(): void
    {

        $this->state->start(10);

        $this->state->skip(1);

        $this->assertMatchesRegularExpression(
            '/\[RUNNING].*skipped: 1,/',
            $this->state->getStatus()->getStatusLine(),
            "unexpected status line"
        );
    }

    public function testSkipWithoutStart(): void
    {
        $this->expectException(LogicException::class);
        $this->state->skip(1);
    }

    public function testError(): void
    {
        $this->state->start(10);

        $this->state->error(new Exception('test'));

        $this->assertMatchesRegularExpression(
            '/\[RUNNING].*errors: 1$/',
            $this->state->getStatus()->getStatusLine(),
            "unexpected status line"
        );
    }

    public function testErrorWithoutStart(): void
    {
        $this->expectException(LogicException::class);
        $this->state->error(new Exception('test'));
    }

    public function testFinish(): void
    {
        $this->state->start(10);

        $this->statusStore->expects($this->once())
            ->method('store');

        $this->state->finish();

        $this->assertMatchesRegularExpression(
            '/\[FINISHED]/',
            $this->state->getStatus()->getStatusLine(),
            "unexpected status line"
        );
    }

    public function testFinishWithoutStart(): void
    {
        $this->expectException(LogicException::class);
        $this->state->finish();
    }

    public function testAbort(): void
    {
        $this->state->start(10);

        $this->state->abort();

        $this->assertMatchesRegularExpression(
            '/\[ABORTED]/',
            $this->state->getStatus()->getStatusLine(),
            "unexpected status line"
        );
    }

    public function testAbortWithoutStart(): void
    {
        $this->expectException(LogicException::class);
        $this->state->abort();
    }

    public function testGetStatus(): void
    {
        $indexName = $this->createMock(IndexName::class);
        $indexName->method('name')->willReturn('test');
        $status = $this->createMock(IndexerStatus::class);
        $statusStore = $this->createMock(IndexerStatusStore::class);
        $statusStore->method('load')
            ->willReturn($status);

        $state = new IndexerProgressState(
            $indexName,
            $statusStore,
            'source'
        );

        $this->assertEquals(
            $status,
            $state->getStatus(),
            'unexpected status'
        );
    }
}
