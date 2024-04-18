<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\ResourceChannelFactory;
use Atoolo\Search\Console\Command\Io\TypifiedInput;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Query\SearchQueryBuilder;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Service\Search\SolrSearch;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'search:search',
    description: 'Performs a search'
)]
class Search extends Command
{
    private SymfonyStyle $io;
    private TypifiedInput $input;

    public function __construct(
        private readonly ResourceChannelFactory $channelFactory,
        private readonly SolrSearch $searcher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command to performs a search')
            ->addArgument(
                'text',
                InputArgument::REQUIRED,
                'Text with which to search.'
            )
            ->addOption(
                'lang',
                null,
                InputArgument::OPTIONAL,
                'Language to be used for the search. (de, en, fr, it, ...)',
                ''
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {

        $this->input = new TypifiedInput($input);
        $this->io = new SymfonyStyle($input, $output);

        $resourceChannel = $this->channelFactory->create();
        $this->io->title('Channel: ' . $resourceChannel->name);

        $query = $this->buildQuery($input);

        $result = $this->searcher->search($query);

        $this->outputResult($result);

        return Command::SUCCESS;
    }

    protected function buildQuery(InputInterface $input): SearchQuery
    {
        $builder = new SearchQueryBuilder();

        $text = $this->input->getStringArgument('text');
        $builder->text($text);

        // TODO: filter

        // TODO: facet

        return $builder->build();
    }

    protected function outputResult(
        SearchResult $result
    ): void {

        if ($result->total === 0) {
            $this->io->text('No results found');
            return;
        }

        $this->io->section('Results (' . $result->total . ')');
        foreach ($result as $resource) {
            $this->io->text($resource->location);
        }

        if (count($result->facetGroups) > 0) {
            $this->io->section('Facets');
            foreach ($result->facetGroups as $facetGroup) {
                $this->io->section($facetGroup->key);
                $listing = [];
                foreach ($facetGroup->facets as $facet) {
                    $listing[] =
                        $facet->key .
                        ' (' . $facet->hits . ')';
                }
                $this->io->listing($listing);
            }
        }

        $this->io->text('Query-Time: ' . $result->queryTime . 'ms');
    }
}
