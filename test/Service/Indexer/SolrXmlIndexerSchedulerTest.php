<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Search\Dto\Indexer\SolrXmlIndexerEvent;
use Atoolo\Search\Service\Indexer\SolrXmlIndexer;
use Atoolo\Search\Service\Indexer\SolrXmlIndexerScheduler;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(SolrXmlIndexerScheduler::class)]
class SolrXmlIndexerSchedulerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testGetSchedule(): void
    {
        $indexer = $this->createStub(SolrXmlIndexer::class);
        $scheduler = new SolrXmlIndexerScheduler(
            '0 2 * * *',
            $indexer,
        );

        $schedule = $scheduler->getSchedule();

        $this->assertCount(
            1,
            $schedule->getRecurringMessages(),
        );
    }

    public function testInvoke(): void
    {
        $indexer = $this->createMock(SolrXmlIndexer::class);
        $scheduler = new SolrXmlIndexerScheduler(
            '0 2 * * *',
            $indexer,
        );

        $indexer->expects($this->once())
            ->method('index');
        $scheduler->__invoke(new SolrXmlIndexerEvent());
    }
}
