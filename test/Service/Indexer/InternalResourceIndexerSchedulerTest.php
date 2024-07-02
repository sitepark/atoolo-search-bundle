<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Search\Dto\Indexer\InternalResourceIndexerEvent;
use Atoolo\Search\Service\Indexer\InternalResourceIndexer;
use Atoolo\Search\Service\Indexer\InternalResourceIndexerScheduler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(InternalResourceIndexerScheduler::class)]
class InternalResourceIndexerSchedulerTest extends TestCase
{
    public function testGetSchedule(): void
    {
        $indexer = $this->createStub(InternalResourceIndexer::class);
        $scheduler = new InternalResourceIndexerScheduler(
            '0 2 * * *',
            $indexer,
        );

        $schedule = $scheduler->getSchedule();

        $this->assertEquals(
            1,
            count($schedule->getRecurringMessages()),
        );
    }

    public function testInvoke(): void
    {
        $indexer = $this->createMock(InternalResourceIndexer::class);
        $scheduler = new InternalResourceIndexerScheduler(
            '0 2 * * *',
            $indexer,
        );

        $indexer->expects($this->once())
            ->method('index');
        $scheduler->__invoke(new InternalResourceIndexerEvent());
    }
}
