<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\Loader\SiteKitLoader;
use Atoolo\Resource\Loader\StaticResourceBaseLocator;
use Atoolo\Search\Dto\Search\Query\MoreLikeThisQuery;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Service\Search\ExternalResourceFactory;
use Atoolo\Search\Service\Search\InternalMediaResourceFactory;
use Atoolo\Search\Service\Search\InternalResourceFactory;
use Atoolo\Search\Service\Search\SolrMoreLikeThis;
use Atoolo\Search\Service\Search\SolrResultToResourceResolver;
use Atoolo\Search\Service\SolrParameterClientFactory;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'atoolo:mlt',
    description: 'Performs a more-like-this search'
)]
class MoreLikeThis extends Command
{
    private SymfonyStyle $io;
    private InputInterface $input;
    private string $solrCore;
    private string $resourceDir;

    protected function configure(): void
    {
        $this
            ->setHelp('Command to performs a more-like-this search')
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
                'Resource directory where the resources can be found.'
            )
            ->addArgument(
                'location',
                InputArgument::REQUIRED,
                'Location of the resource to which the MoreLikeThis ' .
                'search is to be applied..'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);

        $this->solrCore = $this->getStringArgument('solr-core');
        $this->resourceDir = $this->getStringArgument('resource-dir');
        $location = $this->getStringArgument('location');

        $searcher = $this->createSearcher();
        $query = $this->buildQuery($location);
        $result = $searcher->moreLikeThis($query);
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

    protected function createSearcher(): SolrMoreLikeThis
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
        $resourceFactoryList = [
            new ExternalResourceFactory(),
            new InternalResourceFactory($resourceLoader),
            new InternalMediaResourceFactory($resourceLoader)
        ];
        $solrResultToResourceResolver = new SolrResultToResourceResolver(
            $resourceFactoryList
        );

        return new SolrMoreLikeThis(
            $clientFactory,
            $solrResultToResourceResolver
        );
    }

    protected function buildQuery(string $location): MoreLikeThisQuery
    {
        $filterList = [];
        return new MoreLikeThisQuery(
            $this->solrCore,
            $location,
            $filterList,
            5,
            ['content']
        );
    }

    protected function outputResult(SearchResult $result): void
    {
        $this->io->text($result->getTotal() . " Results:");
        foreach ($result as $resource) {
            $this->io->text($resource->getLocation());
        }
        $this->io->text('Query-Time: ' . $result->getQueryTime() . 'ms');
    }
}
