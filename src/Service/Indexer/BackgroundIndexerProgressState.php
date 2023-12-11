<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Search\Dto\Indexer\IndexerStatus;
use DateTime;
use Throwable;
use JsonException;

class BackgroundIndexerProgressState implements IndexerProgressHandler
{
    private IndexerStatus $status;

    public function __construct(private readonly string $file)
    {
    }

    public function start(int $total): void
    {
        $this->status = new IndexerStatus(
            new DateTime(),
            null,
            $total,
            0,
            0
        );
    }

    /**
     * @throws JsonException
     */
    public function advance(int $step): void
    {
        $this->status->processed += $step;
        $this->status->store($this->file);
    }

    public function error(Throwable $throwable): void
    {
        $this->status->errors++;
    }

    /**
     * @throws JsonException
     */
    public function finish(): void
    {
        $this->status->endTime = new DateTime();
        $this->status->store($this->file);
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
