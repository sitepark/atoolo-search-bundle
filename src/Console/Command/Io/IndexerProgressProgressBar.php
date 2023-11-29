<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command\Io;

use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use Exception;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class IndexerProgressProgressBar implements IndexerProgressHandler
{
    private OutputInterface $output;
    private ProgressBar $progressBar;

    private array $errors = [];

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function start(int $total): void
    {
        $this->progressBar = new ProgressBar($this->output, $total);
        $this->formatProgressBar('green');
    }

    public function advance(int $step): void
    {
        $this->progressBar->advance($step);
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

    public function error(Exception $exception): void
    {
        $this->formatProgressBar('red');
        $this->errors[] = $exception;
    }

    public function finish(): void
    {
        $this->progressBar->finish();
    }

    /**
     * @return array<Exception>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
