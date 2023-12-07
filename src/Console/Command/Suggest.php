<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Search\Dto\Search\Query\Filter\ArchiveFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ObjectTypeFilter;
use Atoolo\Search\Dto\Search\Query\SuggestQuery;
use Atoolo\Search\Dto\Search\Result\SuggestResult;
use Atoolo\Search\Service\Search\SolrSuggest;
use Atoolo\Search\Service\SolrParameterClientFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'atoolo:suggest',
    description: 'Performs a suggest search'
)]
class Suggest extends Command
{
    private InputInterface $input;
    private SymfonyStyle $io;
    private string $solrCore;

    protected function configure(): void
    {
        $this
            ->setHelp('Command to performs a suggest search')
            ->addArgument(
                'solr-core',
                InputArgument::REQUIRED,
                'Solr core to be used.'
            )
            ->addArgument(
                'terms',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Suggest terms.'
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $this->input = $input;
        $this->io = new SymfonyStyle($input, $output);
        $this->solrCore = $input->getArgument('solr-core');
        $terms = $input->getArgument('terms');

        $search = $this->createSearcher();
        $query = $this->buildQuery($terms);

        $result = $search->suggest($query);

        $this->outputResult($result);

        return Command::SUCCESS;
    }

    protected function createSearcher(): SolrSuggest
    {
        $clientFactory = new SolrParameterClientFactory();
        $url = parse_url($this->input->getArgument('solr-connection-url'));
        $clientFactory = new SolrParameterClientFactory(
            $url['scheme'],
            $url['host'],
            $url['port'] ?? ($url['scheme'] === 'https' ? 443 : 8983),
            $url['path'] ?? '',
            null,
            0
        );
        return new SolrSuggest($clientFactory);
    }

    protected function buildQuery(array $terms): SuggestQuery
    {
        $excludeMedia = new ObjectTypeFilter('media', 'media');
        $excludeMedia = $excludeMedia->exclude();
        return new SuggestQuery(
            $this->solrCore,
            implode(' ', $terms),
            [
                new ArchiveFilter(),
                $excludeMedia
            ]
        );
    }

    protected function outputResult(SuggestResult $result): void
    {
        foreach ($result as $suggest) {
            $this->io->text(
                $suggest->getTerm() .
                ' (' . $suggest->getHits() . ')'
            );
        }
        $this->io->text('Query-Time: ' . $result->getQueryTime() . 'ms');
    }
}
