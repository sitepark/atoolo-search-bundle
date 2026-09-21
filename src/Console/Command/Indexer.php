<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Index\Console\Command\Indexer as IndexIndexer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated since atoolo/search-bundle 1.18, use `index:indexer` of
 *   atoolo/index-bundle instead. Will be removed in 2.0.
 */
#[AsCommand(
    name: 'search:indexer',
    description: 'Fill a search index (deprecated, use index:indexer)',
)]
class Indexer extends IndexIndexer
{
    use DeprecatedCommandNotice;

    protected function initialize(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->noticeRenamedCommand(
            $output,
            'search:indexer',
            'index:indexer',
        );
        parent::initialize($input, $output);
    }
}
