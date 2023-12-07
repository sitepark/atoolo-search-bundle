<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use DateTime;
use Exception;
use SP\Util\Date;

class BackgroundIndexerProgressState implements IndexerProgressHandler
{
    private BackgroundIndexerStatus $status;

    public function __construct(private readonly string $file)
    {
    }

    public function start(int $total): void
    {
        $this->status = new BackgroundIndexerStatus(
            new \DateTime(),
            null,
            $total,
            0,
            0
        );
    }

    public function advance(int $step): void
    {
        $this->status->processed += $step;
        $this->status->store($this->file);
    }

    public function error(Exception $exception): void
    {
        $this->status->errors++;
    }

    public function finish(): void
    {
        $this->status->endTime = new DateTime();
        $this->status->store($this->file);
    }

    /**
     * @return array<Exception>
     */
    public function getErrors(): array
    {
        return [];
    }

    private function getStatusLine(): string
    {
        return $this->status->getStatusLine();
    }
}
