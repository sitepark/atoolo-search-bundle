<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\Loader\SiteKitLoader;
use Atoolo\Resource\Loader\StaticResourceBaseLocator;
use Atoolo\Search\Dto\Search\Query\SelectQuery;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Service\Search\ExternalResourceFactory;
use Atoolo\Search\Service\Search\InternalMediaResourceFactory;
use Atoolo\Search\Service\Search\InternalResourceFactory;
use Atoolo\Search\Service\Search\SiteKit\DefaultBoostModifier;
use Atoolo\Search\Service\Search\SolrResultToResourceResolver;
use Atoolo\Search\Service\Search\SolrSelect;
use Atoolo\Search\Service\SolrParameterClientFactory;
use InvalidArgumentException;
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
    private InputInterface $input;
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

        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);
        $this->resourceDir = $this->getStringArgument('resource-dir');
        $this->index = $this->getStringArgument('index');

        $searcher = $this->createSearch();
        $query = $this->buildQuery($input);

        $result = $searcher->select($query);

        $this->outputResult($result);

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

    protected function createSearch(): SolrSelect
    {
        $resourceBaseLocator = new StaticResourceBaseLocator(
            $this->resourceDir
        );
        $resourceLoader = new SiteKitLoader($resourceBaseLocator);
        /** @var string[] */
        $url = parse_url($this->getStringArgument('solr-connection-url'));
        $clientFactory = new SolrParameterClientFactory(
            $url['scheme'],
            $url['host'],
            (int)($url['port'] ?? ($url['scheme'] === 'https' ? 443 : 8983)),
            $url['path'] ?? '',
            null,
            0
        );
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
        SearchResult $result
    ): void {
        $this->io->title('Results (' . $result->getTotal() . ')');
        foreach ($result as $resource) {
            $this->io->text($resource->getLocation());
        }

        if (count($result->getFacetGroups()) > 0) {
            $this->io->title('Facets');
            foreach ($result->getFacetGroups() as $facetGroup) {
                $this->io->section($facetGroup->getKey());
                $listing = [];
                foreach ($facetGroup->getFacets() as $facet) {
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
