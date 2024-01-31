<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Dto\Indexer\IndexerStatusState;
use DateTime;
use JsonException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Throwable;

class BackgroundIndexerProgressState implements IndexerProgressHandler
{
    private IndexerStatus $status;

    private bool $isUpdate = false;

    public function __construct(
        private string $index,
        private readonly IndexerStatusStore $statusStore,
        private readonly LoggerInterface $logger = new NullLogger()
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
        $storedStatus = $this->statusStore->load($this->index);
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

    /**
     * @throws JsonException
     */
    public function advance(int $step): void
    {
        $this->status->processed += $step;
        $this->status->lastUpdate = new DateTime();
        if ($this->isUpdate) {
            $this->status->updated += $step;
        }
        $this->statusStore->store($this->index, $this->status);
    }


    public function skip(int $step): void
    {
        $this->status->skipped += $step;
    }

    public function error(Throwable $throwable): void
    {
        $this->status->errors++;
        $this->logger->error(
            $throwable->getMessage(),
            [
                'exception' => $throwable,
            ]
        );
    }

    /**
     * @throws JsonException
     */
    public function finish(): void
    {
        if (!$this->isUpdate) {
            $this->status->endTime = new DateTime();
        }
        if ($this->status->state === IndexerStatusState::RUNNING) {
            $this->status->state = IndexerStatusState::INDEXED;
        }
        $this->statusStore->store($this->index, $this->status);
    }

    public function abort(): void
    {
        $this->status->state = IndexerStatusState::ABORTED;
    }

    /**
     * @return array<Throwable>
     */
    public function getErrors(): array
    {
        return [];
    }

    public function getStatus(): IndexerStatus
    {
        return $this->status;
    }
}
