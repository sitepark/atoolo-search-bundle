<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Console\Command\Io\TypifiedInput;
use Atoolo\Search\Dto\Search\Query\Filter\NotFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ObjectTypeFilter;
use Atoolo\Search\Dto\Search\Query\SuggestQuery;
use Atoolo\Search\Dto\Search\Result\SuggestResult;
use Atoolo\Search\Service\Search\SolrSuggest;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'search:suggest',
    description: 'Performs a suggest search'
)]
class Suggest extends Command
{
    private TypifiedInput $input;
    private SymfonyStyle $io;

    public function __construct(
        private readonly ResourceChannel $channel,
        private readonly SolrSuggest $search
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command that performs a suggest search')
            ->addArgument(
                'terms',
                InputArgument::REQUIRED,
                'Suggest terms.'
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
        $terms = $this->input->getStringArgument('terms');
        $lang = $this->input->getStringOption('lang');

        $this->io->title('Channel: ' . $this->channel->name);

        $query = $this->buildQuery($terms, $lang);

        $result = $this->search->suggest($query);

        $this->outputResult($result);

        return Command::SUCCESS;
    }

    protected function buildQuery(string $terms, string $lang): SuggestQuery
    {
        $excludeMedia = new NotFilter(new ObjectTypeFilter(['media']));
        return new SuggestQuery(
            $terms,
            ResourceLanguage::of($lang),
            [
                $excludeMedia
            ]
        );
    }

    protected function outputResult(SuggestResult $result): void
    {
        if (empty($result->suggestions)) {
            $this->io->text('No suggestions found');
            return;
        }

        foreach ($result as $suggest) {
            $this->io->text(
                $suggest->term .
                ' (' . $suggest->hits . ')'
            );
        }
        $this->io->text('Query-Time: ' . $result->queryTime . 'ms');
    }
}
