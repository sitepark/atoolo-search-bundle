<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\Loader\ServerVarResourceBaseLocator;
use Atoolo\Resource\Loader\SiteKitLoader;
use Atoolo\Search\Console\Command\Io\TypifiedInput;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'atoolo:dump-index-document',
    description: 'Dump a index document'
)]
class DumpIndexDocument extends Command
{
    /**
     * phpcs:ignore
     * @param iterable<DocumentEnricher<IndexDocument>> $documentEnricherList
     */
    public function __construct(
        private readonly iterable $documentEnricherList
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command to dump a index-document')
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
        ;
    }

    /**
     * @throws JsonException
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $typedInput = new TypifiedInput($input);

        $resourceDir = $typedInput->getStringArgument('resource-dir');

        $subDirectory = null;
        if (is_dir($resourceDir . '/objects')) {
            $subDirectory = 'objects';
        }

        $_SERVER['RESOURCE_ROOT'] = $resourceDir;
        $resourceBaseLocator = new ServerVarResourceBaseLocator(
            'RESOURCE_ROOT',
            $subDirectory
        );

        $paths = $typedInput->getArrayArgument('paths');
        $resourceLoader = new SiteKitLoader($resourceBaseLocator);

        foreach ($paths as $path) {
            $resource = $resourceLoader->load($path);
            $doc = new IndexSchema2xDocument();
            $processId = 'process-id';

            foreach ($this->documentEnricherList as $enricher) {
                /** @var IndexSchema2xDocument $doc */
                $doc = $enricher->enrichDocument(
                    $resource,
                    $doc,
                    $processId
                );
            }

            echo json_encode(
                $doc->getFields(),
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
            );
        }

        return Command::SUCCESS;
    }
}
