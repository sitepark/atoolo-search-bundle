<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Search\Dto\Indexer\InternalResourceIndexerEvent;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule(name: 'internal-resource-indexer-scheduler')]
#[AsMessageHandler]
class InternalResourceIndexerScheduler implements ScheduleProviderInterface
{
    private ?Schedule $schedule = null;

    public function __construct(
        private readonly string $cron,
        private readonly InternalResourceIndexer $indexer,
        private readonly LockFactory $lockFactory = new LockFactory(
            new SemaphoreStore(),
        ),
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function getSchedule(): Schedule
    {
        return $this->schedule ??= (new Schedule())
            ->add(
                RecurringMessage::cron(
                    $this->cron,
                    new InternalResourceIndexerEvent()
                ),
            )->lock($this->lockFactory->createLock(
                'internal-resource-indexer-scheduler-'
                        . $this->indexer->getName(),
            ));
    }

    public function __invoke(InternalResourceIndexerEvent $message): void
    {
        $status = $this->indexer->index();
        $this->logger->info("indexer finish: " . $status->getStatusLine());
    }
}
