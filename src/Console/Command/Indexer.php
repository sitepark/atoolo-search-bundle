<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\ResourceChannelFactory;
use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use Atoolo\Search\Console\Command\Io\TypifiedInput;
use Atoolo\Search\Service\Indexer\IndexerCollection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'search:indexer',
    description: 'Fill a search index'
)]
class Indexer extends Command
{
    private SymfonyStyle $io;
    private OutputInterface $output;

    public function __construct(
        private readonly ResourceChannelFactory $channelFactory,
        private readonly IndexerProgressBar $progressBar,
        private readonly IndexerCollection $indexers,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command to fill a search index')
            ->addArgument(
                'paths',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Resources paths or directories of resources to be indexed.'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $typedInput = new TypifiedInput($input);
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        $paths = $typedInput->getArrayArgument('paths');

        $resourceChannel = $this->channelFactory->create();
        $this->io->title('Channel: ' . $resourceChannel->name);

        /*
        if (empty($paths)) {
            $this->io->section('Index all resources');
        } else {
            $this->io->section('Index resource paths');
            $this->io->listing($paths);
        }
        */

        foreach ($this->indexers->getIndexers() as $indexer) {
            if ($indexer->enabled()) {
                $this->io->newLine();
                $this->io->section(
                    'Index with Indexer "' . $indexer->getName() . '"'
                );
                $progressHandler = $indexer->getProgressHandler();
                $this->progressBar->init($progressHandler);
                $indexer->setProgressHandler($this->progressBar);
                try {
                    $status = $indexer->index();
                } finally {
                    $indexer->setProgressHandler($progressHandler);
                }
                $this->io->newLine(2);
                $this->io->section("Status");
                $this->io->text($status->getStatusLine());
                $this->io->newLine();
                $this->errorReport();
            }
        }


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
