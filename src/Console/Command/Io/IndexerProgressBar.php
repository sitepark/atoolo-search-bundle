<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command\Io;

use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use JsonException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Throwable;

class IndexerProgressBar implements IndexerProgressHandler
{
    private ?ProgressBar $progressBar;

    private IndexerProgressHandler $currentProgressHandler;

    private int $prepareLines = 0;

    /**
     * @var array<Throwable>
     */
    private array $errors = [];

    public function __construct(
        private readonly OutputInterface $output = new ConsoleOutput()
    ) {
    }

    public function init(
        IndexerProgressHandler $progressHandler
    ): void {
        $this->currentProgressHandler = $progressHandler;
        $this->errors = [];
        $this->progressBar = null;
    }

    public function prepare(string $message): void
    {
        $this->currentProgressHandler->prepare($message);
        $this->output->writeln($message);
        $this->prepareLines++;
    }

    public function start(int $total): void
    {
        if ($this->prepareLines > 0) {
            $this->output->write("\x1b[" . $this->prepareLines . "A");
        }
        $this->currentProgressHandler->start($total);
        $this->progressBar = new ProgressBar($this->output, $total);
        $this->formatProgressBar('green');
    }

    /**
     * @throws ExceptionInterface
     */
    public function startUpdate(int $total): void
    {
        $this->currentProgressHandler->startUpdate($total);
        $this->progressBar = new ProgressBar($this->output, $total);
        $this->formatProgressBar('green');
    }

    /**
     * @throws JsonException
     */
    public function advance(int $step): void
    {
        $this->currentProgressHandler->advance($step);
        $this->progressBar?->advance($step);
    }

    public function skip(int $step): void
    {
        $this->currentProgressHandler->skip($step);
    }

    private function formatProgressBar(string $color): void
    {
        $this->progressBar?->setBarCharacter('<fg=' . $color . '>•</>');
        $this->progressBar?->setEmptyBarCharacter('<fg=' . $color . '>⚬</>');
        $this->progressBar?->setProgressCharacter('<fg=' . $color . '>➤</>');
        $this->progressBar?->setFormat(
            "%current%/%max% [%bar%] %percent:3s%%\n" .
            "  %estimated:-20s%  %memory:20s%"
        );
    }

    public function error(Throwable $throwable): void
    {
        $this->currentProgressHandler->error($throwable);
        $this->formatProgressBar('red');
        $this->errors[] = $throwable;
    }

    /**
     * @throws JsonException
     */
    public function finish(): void
    {
        $this->currentProgressHandler->finish();
        $this->progressBar?->finish();
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
        return $this->currentProgressHandler->getStatus();
    }

    public function abort(): void
    {
        $this->currentProgressHandler->abort();
        $this->progressBar?->finish();
    }
}
