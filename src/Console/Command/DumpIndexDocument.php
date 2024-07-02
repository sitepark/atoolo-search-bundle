<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\ResourceChannel;
use Atoolo\Search\Console\Command\Io\TypifiedInput;
use Atoolo\Search\Service\Indexer\IndexDocumentDumper;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'search:dump-index-document',
    description: 'Dump a index document',
)]
class DumpIndexDocument extends Command
{
    public function __construct(
        private readonly ResourceChannel $channel,
        private readonly IndexDocumentDumper $dumper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('Command to dump a index-document')
            ->addArgument(
                'paths',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Resources paths or directories of resources to be indexed.',
            )
        ;
    }

    /**
     * @throws JsonException
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {

        $typedInput = new TypifiedInput($input);

        $paths = $typedInput->getArrayArgument('paths');

        $io = new SymfonyStyle($input, $output);
        $io->title('Channel: ' . $this->channel->name);

        $dump = $this->dumper->dump($paths);

        foreach ($dump as $fields) {
            $output->writeln(json_encode(
                $fields,
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT,
            ));
        }

        return Command::SUCCESS;
    }
}
