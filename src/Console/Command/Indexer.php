<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\ResourceChannel;
use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use Atoolo\Search\Console\Command\Io\TypifiedInput;
use Atoolo\Search\Service\Indexer\IndexerCollection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'search:indexer',
    description: 'Fill a search index',
)]
class Indexer extends Command
{
    private SymfonyStyle $io;
    private InputInterface $input;
    private OutputInterface $output;

    public function __construct(
        private readonly ResourceChannel $channel,
        private readonly IndexerProgressBar $progressBar,
        private readonly IndexerCollection $indexers,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command to fill a search index')
            ->addArgument(
                'paths',
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Resources paths or directories of resources to be indexed.',
            )
            ->addOption(
                'source',
                null,
                InputArgument::OPTIONAL,
                'Uses only the indexer of a specific source',
                '',
            )
        ;
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {

        $typedInput = new TypifiedInput($input);
        $this->input = $input;
        $this->output = $output;
        $this->io = new SymfonyStyle($input, $output);

        $source = $typedInput->getStringOption('source');

        $this->io->title('Channel: ' . $this->channel->name);

        $selectableIndexer = $this->getSelectableIndexer($source);

        if (empty($selectableIndexer)) {
            $this->io->error('No indexer available');
            return Command::FAILURE;
        }

        $indexer = $this->selectIndexer($selectableIndexer);
        $this->index($indexer);
        return Command::SUCCESS;
    }

    /**
     * @return \Atoolo\Search\Indexer[]
     */
    private function getSelectableIndexer(?string $source): array
    {
        $selectableIndexer = [];

        foreach ($this->indexers->getIndexers() as $indexer) {
            $s = $indexer->getSource();
            $n = $indexer->getName();
            if (!empty($source) && $indexer->getSource() !== $source) {
                continue;
            }
            if ($indexer->enabled()) {
                $selectableIndexer[] = $indexer;
            }
        }

        return $selectableIndexer;
    }

    /**
     * @param \Atoolo\Search\Indexer[] $selectable
     */
    private function selectIndexer(array $selectable): \Atoolo\Search\Indexer
    {
        if (count($selectable) === 1) {
            return $selectable[0];
        }

        $names = [];
        foreach ($selectable as $indexer) {
            $names[] = $indexer->getName() .
                ' (source: ' . $indexer->getSource() . ')';
        }
        $this->io->newLine();
        $this->io->section('Several indexers are available.');

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ChoiceQuestion(
            'Please select the indexer you want to use [0]',
            $names,
        );
        $question->setErrorMessage('Indexer %s is invalid.');

        $selectedName = $helper->ask($this->input, $this->output, $question);
        $this->io->text('You have just selected: ' . $selectedName);

        $pos = array_search($selectedName, $names, true);
        return $selectable[$pos];
    }

    private function index(\Atoolo\Search\Indexer $indexer): void
    {
        $this->io->newLine();
        $this->io->section(
            'Index with Indexer "' . $indexer->getName() . '" ' .
            '(source: ' . $indexer->getSource() . ')',
        );
        $progressHandler = $indexer->getProgressHandler();
        $this->progressBar->init($progressHandler);
        $indexer->setProgressHandler($this->progressBar);
        try {
            $status = $indexer->index();
        } finally {
            $indexer->setProgressHandler($progressHandler);
        }
        $this->io->newLine(2);
        $this->io->section("Status");
        $this->io->text($status->getStatusLine());
        $this->io->newLine();
        $this->errorReport();
    }

    protected function errorReport(): void
    {
        if (empty($this->progressBar->getErrors())) {
            return;
        }
        $this->io->section("Error Report");

        foreach ($this->progressBar->getErrors() as $error) {
            if ($this->io->isVerbose() && $this->getApplication() !== null) {
                $this->getApplication()->renderThrowable($error, $this->output);
            } else {
                $this->io->error($error->getMessage());
            }
        }
    }
}
