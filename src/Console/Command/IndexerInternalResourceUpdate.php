<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\ResourceChannel;
use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use Atoolo\Search\Console\Command\Io\TypifiedInput;
use Atoolo\Search\Service\Indexer\InternalResourceIndexer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'search:indexer:update-internal-resources',
    description: 'Update internal resources in search index',
)]
class IndexerInternalResourceUpdate extends Command
{
    private SymfonyStyle $io;
    private OutputInterface $output;

    public function __construct(
        private readonly ResourceChannel $channel,
        private readonly IndexerProgressBar $progressBar,
        private readonly InternalResourceIndexer $indexer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command to update internal resources in search index')
            ->addArgument(
                'paths',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Resources paths or directories of resources to be updated.',
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {

        $typedInput = new TypifiedInput($input);
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        $paths = $typedInput->getArrayArgument('paths');

        $this->io->title('Channel: ' . $this->channel->name);

        $this->io->section(
            'Index resource paths with Indexer "' .
            $this->indexer->getName() . '"',
        );
        $this->io->listing($paths);
        $progressHandler = $this->indexer->getProgressHandler();
        $this->progressBar->init($progressHandler);
        $this->indexer->setProgressHandler($this->progressBar);
        try {
            $status = $this->indexer->update($paths);
        } finally {
            $this->indexer->setProgressHandler($progressHandler);
        }
        $this->io->newLine(2);
        $this->io->section("Status");
        $this->io->text($status->getStatusLine());
        $this->io->newLine();
        $this->errorReport();


        return Command::SUCCESS;
    }

    protected function errorReport(): void
    {
        if (empty($this->progressBar->getErrors())) {
            return;
        }
        $this->io->section("Error Report");

        foreach ($this->progressBar->getErrors() as $error) {
            if ($this->io->isVerbose() && $this->getApplication() !== null) {
                $this->getApplication()->renderThrowable($error, $this->output);
            } else {
                $this->io->error($error->getMessage());
            }
        }
    }
}
