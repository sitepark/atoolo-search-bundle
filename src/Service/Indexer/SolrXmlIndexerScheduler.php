<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Indexer\SolrXmlIndexerEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsMessageHandler]
class SolrXmlIndexerScheduler implements ScheduleProviderInterface
{
    private ?Schedule $schedule = null;

    public function __construct(
        private readonly string $cron,
        private readonly SolrXmlIndexer $indexer,
        private readonly LockFactory $lockFactory = new LockFactory(
            new SemaphoreStore(),
        ),
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    public function getSchedule(): Schedule
    {
        $source = $this->indexer->getSource();
        $name = $this->indexer->getIndex(ResourceLanguage::default());
        return $this->schedule ??= (new Schedule())
            ->add(
                RecurringMessage::cron(
                    $this->cron,
                    new SolrXmlIndexerEvent(),
                ),
            )->lock($this->lockFactory->createLock(
                'solr-xml-indexer-scheduler-'
                        . $source
                        . '-'
                        . $name,
            ));
    }

    public function __invoke(SolrXmlIndexerEvent $message): void
    {
        $status = $this->indexer->index();
        $this->logger->info("indexer finish: " . $status->getStatusLine());
    }
}
