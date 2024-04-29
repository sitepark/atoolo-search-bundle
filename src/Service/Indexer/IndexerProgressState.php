<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Dto\Indexer\IndexerStatusState;
use Atoolo\Search\Service\IndexName;
use DateTime;
use LogicException;
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

    public function prepare(string $message): void
    {
        $this->status = new IndexerStatus(
            state: IndexerStatusState::PREPARING,
            startTime: new DateTime(),
            endTime: null,
            total: 0,
            processed: 0,
            skipped: 0,
            lastUpdate: new DateTime(),
            updated: 0,
            errors: 0,
            prepareMessage: $message,
        );
        $this->statusStore->store(
            $this->getStatusStoreKey(),
            $this->status
        );
    }

    /**
     * @throws ExceptionInterface
     */
    public function start(int $total): void
    {
        $storedStatus = $this->statusStore->load($this->getStatusStoreKey());

        $startTime = new DateTime();
        if ($storedStatus->state === IndexerStatusState::PREPARING) {
            $startTime = $storedStatus->startTime;
        }

        $this->status = new IndexerStatus(
            IndexerStatusState::RUNNING,
            $startTime,
            null,
            $total,
            0,
            0,
            new DateTime(),
            0,
            0
        );
        $this->statusStore->store(
            $this->getStatusStoreKey(),
            $this->status
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
        $this->statusStore->store(
            $this->getStatusStoreKey(),
            $this->status
        );
    }

    public function advance(int $step): void
    {
        if ($this->status === null) {
            throw new LogicException(
                'Cannot advance without starting the progress'
            );
        }
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
        if ($this->status === null) {
            throw new LogicException(
                'Cannot advance without starting the progress'
            );
        }
        $this->status->skipped += $step;
    }

    public function error(Throwable $throwable): void
    {
        if ($this->status === null) {
            throw new LogicException(
                'Cannot advance without starting the progress'
            );
        }
        $this->status->errors++;
    }

    public function finish(): void
    {
        if ($this->status === null) {
            throw new LogicException(
                'Cannot advance without starting the progress'
            );
        }
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
        if ($this->status === null) {
            throw new LogicException(
                'Cannot advance without starting the progress'
            );
        }
        $this->status->state = IndexerStatusState::ABORTED;
    }

    /**
     * @throws ExceptionInterface
     */
    public function getStatus(): IndexerStatus
    {
        return $this->status
            ?? $this->statusStore->load($this->getStatusStoreKey());
    }

    private function getStatusStoreKey(): string
    {
        return $this->index->name(ResourceLanguage::default()) .
            '-' . $this->source;
    }
}
