<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Search\Console\Command\Io\IndexerProgressProgressBar;
use Atoolo\Search\Dto\Indexer\IndexerParameter;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use InvalidArgumentException;
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
    private IndexerProgressProgressBar $progressBar;
    private SymfonyStyle $io;

    private InputInterface $input;

    /**
     * phpcs:ignore
     * @param iterable<DocumentEnricher<IndexDocument>> $documentEnricherList
     */
    public function __construct(
        private readonly iterable $documentEnricherList,
        private readonly SolrIndexerBuilder $solrIndexerBuilder
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

        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);
        $this->progressBar = new IndexerProgressProgressBar($output);

        $paths = $this->getArrayArgument('paths');

        $cleanupThreshold = empty($paths)
            ? $this->getIntOption('cleanup-threshold')
            : 0;

        if (empty($paths)) {
            $this->io->title('Index all resources');
        } else {
            $this->io->title('Index resource paths');
            $this->io->listing($paths);
        }

        $parameter = new IndexerParameter(
            $this->getStringArgument('solr-core'),
            $cleanupThreshold,
            $this->getIntOption('chunk-size'),
            $paths
        );

        $this->solrIndexerBuilder
            ->resourceDir($this->getStringArgument('resource-dir'))
            ->progressBar($this->progressBar)
            ->documentEnricherList($this->documentEnricherList)
            ->solrConnectionUrl(
                $this->getStringArgument('solr-connection-url')
            );

        $indexer = $this->solrIndexerBuilder->build();
        $indexer->index($parameter);

        $this->errorReport();

        return Command::SUCCESS;
    }

    private function getStringArgument(string $name): string
    {
        $value = $this->input->getArgument($name);
        if (!is_string($value)) {
            throw new InvalidArgumentException(
                $name . ' must be a string'
            );
        }
        return $value;
    }

    private function getIntOption(string $name): int
    {
        $value = $this->input->getOption($name);
        if (!is_numeric($value)) {
            throw new InvalidArgumentException(
                $name . ' must be a integer: ' . $value
            );
        }
        return (int)$value;
    }

    /**
     * @return string[]
     */
    private function getArrayArgument(string $name): array
    {
        $value = $this->input->getArgument($name);
        if (!is_array($value)) {
            throw new InvalidArgumentException(
                $name . ' must be a array'
            );
        }
        return $value;
    }

    protected function errorReport(): void
    {
        foreach ($this->progressBar->getErrors() as $error) {
            $this->io->error($error->getMessage());
        }
    }
}
