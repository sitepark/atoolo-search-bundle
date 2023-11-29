<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Loader\SiteKitLoader;
use Atoolo\Resource\Loader\SiteKitNavigationHierarchyLoader;
use Atoolo\Search\Console\Command\Io\IndexerProgressProgressBar;
use Atoolo\Search\Dto\Indexer\IndexerParameter;
use Atoolo\Search\Service\Indexer\SiteKit\DefaultSchema21DocumentEnricher;
use Atoolo\Search\Service\Indexer\SolrIndexer;
use Atoolo\Search\Service\SolrParameterClientFactory;
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
    private string $resourceDir;

    protected function configure(): void
    {
        $this
            ->setHelp('Command to fill a search index')
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
                'directories',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Resources or directories of the resource to be indexed.'
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
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $this->io = new SymfonyStyle($input, $output);
        $this->progressBar = new IndexerProgressProgressBar($output);
        $this->resourceDir = $input->getArgument('resource-dir');
        $directories = $input->getArgument('directories');

        $cleanupThreshold = empty($directories)
            ? $input->getArgument('cleanup-threshold')
            : 0;

        if (empty($directories)) {
            $this->io->title('Index all resources');
        } else {
            $this->io->title('Index resources subdirectories');
            $this->io->listing($directories);
        }

        $parameter = new IndexerParameter(
            $input->getArgument('solr-core'),
            $this->resourceDir,
            $cleanupThreshold,
            $directories
        );

        $indexer = $this->createIndexer();
        $indexer->index($parameter);

        $this->errorReport();

        return Command::SUCCESS;
    }

    protected function errorReport(): void
    {
        foreach ($this->progressBar->getErrors() as $error) {
            if ($error instanceof InvalidResourceException) {
                $this->io->error(
                    $error->getLocation() . ': ' .
                    $error->getMessage()
                );
            } else {
                $this->io->error($error->getMessage());
            }
        }
    }

    protected function createIndexer(): SolrIndexer
    {
        $resourceLoader = new SiteKitLoader($this->resourceDir);
        $navigationLoader = new SiteKitNavigationHierarchyLoader(
            $resourceLoader
        );
        $schema21 = new DefaultSchema21DocumentEnricher(
            $navigationLoader
        );

        $clientFactory = new SolrParameterClientFactory();
        return new SolrIndexer(
            [$schema21],
            $this->progressBar,
            $resourceLoader,
            $clientFactory,
            'internal'
        );
    }
}
