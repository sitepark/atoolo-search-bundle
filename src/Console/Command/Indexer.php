<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Loader\SiteKitLoader;
use Atoolo\Resource\Loader\SiteKitNavigationHierarchyLoader;
use Atoolo\Resource\Loader\StaticResourceBaseLocator;
use Atoolo\Search\Console\Command\Io\IndexerProgressProgressBar;
use Atoolo\Search\Dto\Indexer\IndexerParameter;
use Atoolo\Search\Service\Indexer\IndexingAborter;
use Atoolo\Search\Service\Indexer\LocationFinder;
use Atoolo\Search\Service\Indexer\SiteKit\DefaultSchema2xDocumentEnricher;
use Atoolo\Search\Service\Indexer\SiteKit\SubDirTranslationSplitter;
use Atoolo\Search\Service\Indexer\SolrIndexer;
use Atoolo\Search\Service\SolrParameterClientFactory;
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
    private string $resourceDir;

    public function __construct(private readonly iterable $documentEnricherList)
    {
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
        $this->resourceDir = $this->getStringArgument('resource-dir');
        $_SERVER['RESOURCE_ROOT'] = $this->resourceDir;
        $paths = (array)$input->getArgument('paths');

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

        $indexer = $this->createIndexer();
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

    protected function errorReport(): void
    {
        foreach ($this->progressBar->getErrors() as $error) {
            $this->io->error($error->getMessage());
        }
    }

    protected function createIndexer(): SolrIndexer
    {
        $resourceBaseLocator = new StaticResourceBaseLocator(
            $this->resourceDir
        );
        $finder = new LocationFinder($resourceBaseLocator);
        $resourceLoader = new SiteKitLoader($resourceBaseLocator);
        $navigationLoader = new SiteKitNavigationHierarchyLoader(
            $resourceLoader
        );
        $schema21 = new DefaultSchema2xDocumentEnricher(
            $navigationLoader
        );

        $documentEnricherList = [$schema21];
        foreach ($this->documentEnricherList as $enricher) {
            $documentEnricherList[] = $enricher;
        }
        $url = parse_url($this->getStringArgument('solr-connection-url'));

        $clientFactory = new SolrParameterClientFactory(
            $url['scheme'],
            $url['host'],
            $url['port'] ?? ($url['scheme'] === 'https' ? 443 : 8382),
            $url['path'] ?? '',
            null,
            0
        );

        $translationSplitter = new SubDirTranslationSplitter();

        $aborter = new IndexingAborter('.');

        return new SolrIndexer(
            $documentEnricherList,
            $this->progressBar,
            $finder,
            $resourceLoader,
            $translationSplitter,
            $clientFactory,
            $aborter,
            'internal'
        );
    }
}
