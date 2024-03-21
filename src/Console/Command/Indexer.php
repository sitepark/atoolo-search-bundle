<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use Atoolo\Search\Console\Command\Io\TypifiedInput;
use Atoolo\Search\Dto\Indexer\IndexerParameter;
use Atoolo\Search\Service\Indexer\InternalResourceIndexer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'atoolo:indexer',
    description: 'Fill a search index'
)]
class Indexer extends Command
{
    private SymfonyStyle $io;
    private OutputInterface $output;

    public function __construct(
        private readonly IndexerProgressBar $progressBar,
        private readonly InternalResourceIndexer $indexer,
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
            ->addOption(
                'cleanup-threshold',
                null,
                InputArgument::OPTIONAL,
                'Specifies the number of documents required to be indexed ' .
                'successfully for the entire process to be considered ' .
                'successfull. Old entries will only ever be removed if this ' .
                'threshold is reached. Only relevant for full-indexing.',
                0
            )
            ->addOption(
                'chunk-size',
                null,
                InputArgument::OPTIONAL,
                'The chunk size determines how many documents are ' .
                'indexed in an update request. The default value is 500. ' .
                'Higher values no longer have a positive effect. Smaller ' .
                'values can be selected if the memory limit is reached.',
                500
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

        $cleanupThreshold = empty($paths)
            ? $typedInput->getIntOption('cleanup-threshold')
            : 0;

        if (empty($paths)) {
            $this->io->title('Index all resources');
        } else {
            $this->io->title('Index resource paths');
            $this->io->listing($paths);
        }

        $parameter = new IndexerParameter(
            $cleanupThreshold,
            $typedInput->getIntOption('chunk-size'),
            $paths
        );

        $this->indexer->index($parameter);

        $this->errorReport();

        return Command::SUCCESS;
    }

    protected function errorReport(): void
    {
        foreach ($this->progressBar->getErrors() as $error) {
            if ($this->io->isVerbose() && $this->getApplication() !== null) {
                $this->getApplication()->renderThrowable($error, $this->output);
            } else {
                $this->io->error($error->getMessage());
            }
        }
    }
}
