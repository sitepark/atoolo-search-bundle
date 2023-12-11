<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command\Io;

use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use DateTime;
use Throwable;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class IndexerProgressProgressBar implements IndexerProgressHandler
{
    private OutputInterface $output;
    private ProgressBar $progressBar;
    private IndexerStatus $status;

    private array $errors = [];

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function start(int $total): void
    {
        $this->progressBar = new ProgressBar($this->output, $total);
        $this->formatProgressBar('green');
        $this->status = new IndexerStatus(
            new DateTime(),
            null,
            $total,
            0,
            0
        );
    }

    public function advance(int $step): void
    {
        $this->progressBar->advance($step);
        $this->status->processed += $step;
    }

    private function formatProgressBar(string $color): void
    {
        $this->progressBar->setBarCharacter('<fg=' . $color . '>•</>');
        $this->progressBar->setEmptyBarCharacter('<fg=' . $color . '>⚬</>');
        $this->progressBar->setProgressCharacter('<fg=' . $color . '>➤</>');
        $this->progressBar->setFormat(
            "%current%/%max% [%bar%] %percent:3s%%\n" .
                   "  %estimated:-20s%  %memory:20s%"
        );
    }

    public function error(Throwable $throwable): void
    {
        $this->formatProgressBar('red');
        $this->errors[] = $throwable;
        $this->status->errors++;
    }

    public function finish(): void
    {
        $this->progressBar->finish();
        $this->status->endTime = new DateTime();
    }

    /**
     * @return array<Throwable>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getStatus(): IndexerStatus
    {
        return $this->status;
    }
}
