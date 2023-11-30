<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\Loader\SiteKitLoader;
use Atoolo\Search\Dto\Search\Query\SelectQuery;
use Atoolo\Search\Dto\Search\Result\ResourceSearchResult;
use Atoolo\Search\Service\Search\ExternalResourceFactory;
use Atoolo\Search\Service\Search\InternalMediaResourceFactory;
use Atoolo\Search\Service\Search\InternalResourceFactory;
use Atoolo\Search\Service\Search\SiteKit\DefaultBoostModifier;
use Atoolo\Search\Service\Search\SolrResultToResourceResolver;
use Atoolo\Search\Service\Search\SolrSelect;
use Atoolo\Search\Service\SolrParameterClientFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'atoolo:search',
    description: 'Performs a search'
)]
class Search extends Command
{
    private SymfonyStyle $io;
    private string $index;
    private string $resourceDir;


    protected function configure(): void
    {
        $this
            ->setHelp('Command to performs a search')
            ->addArgument(
                'index',
                InputArgument::REQUIRED,
                'Solr core to be used.'
            )
            ->addArgument(
                'resource-dir',
                InputArgument::REQUIRED,
                'Resource directory whose data is to be indexed.'
            )
            ->addArgument(
                'text',
                InputArgument::IS_ARRAY,
                'Text with which to search.'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $this->io = new SymfonyStyle($input, $output);
        $this->resourceDir = $input->getArgument('resource-dir');
        $this->index = $input->getArgument('index');

        $searcher = $this->createSearch();
        $query = $this->buildQuery($input);

        $result = $searcher->select($query);

        $this->outputResult($result);

        return Command::SUCCESS;
    }

    protected function createSearch(): SolrSelect
    {
        $resourceLoader = new SiteKitLoader($this->resourceDir);
        $clientFactory = new SolrParameterClientFactory();
        $defaultBoosting = new DefaultBoostModifier();

        $resourceFactoryList = [
            new ExternalResourceFactory(),
            new InternalResourceFactory($resourceLoader),
            new InternalMediaResourceFactory($resourceLoader)
        ];

        $solrResultToResourceResolver = new SolrResultToResourceResolver(
            $resourceFactoryList
        );

        return new SolrSelect(
            $clientFactory,
            [$defaultBoosting],
            $solrResultToResourceResolver
        );
    }

    protected function buildQuery(InputInterface $input): SelectQuery
    {
        $builder = SelectQuery::builder();
        $builder->index($this->index);

        $text = $input->getArgument('text');
        if (is_array($text)) {
            $builder->text(implode(' ', $text));
        }

        // TODO: filter

        // TODO: facet

        return $builder->build();
    }

    protected function outputResult(
        ResourceSearchResult $result
    ) {
        $this->io->title('Results (' . $result->getTotal() . ')');
        foreach ($result as $resource) {
            $this->io->text($resource->getLocation());
        }

        if (count($result->getFacetGroupList()) > 0) {
            $this->io->title('Facets');
            foreach ($result->getFacetGroupList() as $facetGroup) {
                $this->io->section($facetGroup->getKey());
                $listing = [];
                foreach ($facetGroup->getFacetList() as $facet) {
                    $listing[] =
                        $facet->getKey() .
                        ' (' . $facet->getHits() . ')';
                }
                $this->io->listing($listing);
            }
        }

        $this->io->text('Query-Time: ' . $result->getQueryTime() . 'ms');
    }
}
