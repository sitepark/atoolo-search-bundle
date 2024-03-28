<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Dto\Indexer\IndexerStatusState;
use Atoolo\Search\Service\IndexName;
use DateTime;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Throwable;

class IndexerProgressState implements IndexerProgressHandler
{
    private ?IndexerStatus $status = null;

    private bool $isUpdate = false;

    public function __construct(
        private readonly IndexName $index,
        private readonly IndexerStatusStore $statusStore,
        private readonly string $source,
    ) {
    }

    public function start(int $total): void
    {
        $this->status = new IndexerStatus(
            IndexerStatusState::RUNNING,
            new DateTime(),
            null,
            $total,
            0,
            0,
            new DateTime(),
            0,
            0
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function startUpdate(int $total): void
    {
        $this->isUpdate = true;
        $storedStatus = $this->statusStore->load($this->getStatusStoreKey());
        $this->status = new IndexerStatus(
            IndexerStatusState::RUNNING,
            $storedStatus->startTime,
            $storedStatus->endTime,
            $storedStatus->total + $total,
            $storedStatus->processed,
            $storedStatus->skipped,
            new DateTime(),
            $storedStatus->updated,
            $storedStatus->errors,
        );
    }

    public function advance(int $step): void
    {
        $this->status->processed += $step;
        $this->status->lastUpdate = new DateTime();
        if ($this->isUpdate) {
            $this->status->updated += $step;
        }
        $this->statusStore->store(
            $this->getStatusStoreKey(),
            $this->status
        );
    }

    public function skip(int $step): void
    {
        $this->status->skipped += $step;
    }

    public function error(Throwable $throwable): void
    {
        $this->status->errors++;
    }

    public function finish(): void
    {
        if (!$this->isUpdate) {
            $this->status->endTime = new DateTime();
        }
        if ($this->status->state === IndexerStatusState::RUNNING) {
            $this->status->state = IndexerStatusState::FINISHED;
        }
        $this->statusStore->store($this->getStatusStoreKey(), $this->status);
    }

    public function abort(): void
    {
        $this->status->state = IndexerStatusState::ABORTED;
    }

    public function getStatus(): IndexerStatus
    {
        if ($this->status !== null) {
            return $this->status;
        }
        return $this->statusStore->load($this->getStatusStoreKey());
    }

    private function getStatusStoreKey(): string
    {
        return $this->index->name('') . '-' . $this->source;
    }
}
