<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Search\Console\Command\Io\TypifiedInput;
use Atoolo\Search\Dto\Search\Query\MoreLikeThisQuery;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Service\Search\SolrMoreLikeThis;
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
    private TypifiedInput $input;

    public function __construct(
        private readonly SolrMoreLikeThis $searcher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command to performs a more-like-this search')
            ->addArgument(
                'location',
                InputArgument::REQUIRED,
                'Location of the resource to which the MoreLikeThis ' .
                'search is to be applied..'
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

        $location = $this->input->getStringArgument('location');
        $lang = $this->input->getStringOption('lang');

        $query = $this->buildQuery($location, $lang);
        $result = $this->searcher->moreLikeThis($query);
        $this->outputResult($result);

        return Command::SUCCESS;
    }

    protected function buildQuery(
        string $location,
        string $lang
    ): MoreLikeThisQuery {
        $filterList = [];
        return new MoreLikeThisQuery(
            $location,
            $lang,
            $filterList,
            5,
            ['content']
        );
    }

    protected function outputResult(SearchResult $result): void
    {
        $this->io->text($result->total . " Results:");
        foreach ($result as $resource) {
            $this->io->text($resource->getLocation());
        }
        $this->io->text('Query-Time: ' . $result->queryTime . 'ms');
    }
}
