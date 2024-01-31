<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use Atoolo\Search\Console\Command\Io\IndexerProgressBarFactory;
use Atoolo\Search\Console\Command\Io\TypifiedInput;
use Atoolo\Search\Dto\Indexer\IndexerParameter;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
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
    private IndexerProgressBar $progressBar;
    private SymfonyStyle $io;
    private TypifiedInput $input;

    /**
     * phpcs:ignore
     * @param iterable<DocumentEnricher<IndexDocument>> $documentEnricherList
     */
    public function __construct(
        private readonly iterable $documentEnricherList,
        private readonly SolrIndexerBuilder $solrIndexerBuilder,
        private readonly IndexerProgressBarFactory $progressBarFactory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command to fill a search index')
            ->addArgument(
                'solr-connection-url',
                InputArgument::REQUIRED,
                'Solr connection url.'
            )
            ->addArgument(
                'solr-core',
                InputArgument::REQUIRED,
                'Solr core to be used.'
            )
            ->addArgument(
                'resource-dir',
                InputArgument::REQUIRED,
                'Resource directory whose data is to be indexed.'
            )
            ->addArgument(
                'paths',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Resources paths or directories of resources to be indexed.'
            )
            ->addOption(
                'cleanup-threshold',
                null,
                InputArgument::OPTIONAL,
                'Specifies the number of indexed documents from ' .
                'which indexing is considered successful and old entries ' .
                'can be deleted. Is only used for full indexing.',
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

        $this->input = new TypifiedInput($input);
        $this->io = new SymfonyStyle($input, $output);
        $this->progressBar = $this->progressBarFactory->create($output);

        $paths = $this->input->getArrayArgument('paths');

        $cleanupThreshold = empty($paths)
            ? $this->input->getIntOption('cleanup-threshold')
            : 0;

        if (empty($paths)) {
            $this->io->title('Index all resources');
        } else {
            $this->io->title('Index resource paths');
            $this->io->listing($paths);
        }

        $parameter = new IndexerParameter(
            $this->input->getStringArgument('solr-core'),
            $cleanupThreshold,
            $this->input->getIntOption('chunk-size'),
            $paths
        );

        $this->solrIndexerBuilder
            ->resourceDir($this->input->getStringArgument('resource-dir'))
            ->progressBar($this->progressBar)
            ->documentEnricherList($this->documentEnricherList)
            ->solrConnectionUrl(
                $this->input->getStringArgument('solr-connection-url')
            );

        $indexer = $this->solrIndexerBuilder->build();
        $indexer->index($parameter);

        $this->errorReport();

        return Command::SUCCESS;
    }

    protected function errorReport(): void
    {
        foreach ($this->progressBar->getErrors() as $error) {
            $this->io->error($error->getMessage());
        }
    }
}
