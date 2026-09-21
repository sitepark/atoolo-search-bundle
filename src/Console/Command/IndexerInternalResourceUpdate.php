<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Index\Console\Command\IndexerInternalResourceUpdate as IndexUpdate;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated since atoolo/search-bundle 1.18, use `index:update` of
 *   atoolo/index-bundle instead. Will be removed in 2.0.
 */
#[AsCommand(
    name: 'search:indexer:update-internal-resources',
    description: 'Update internal resources in search index '
        . '(deprecated, use index:update)',
)]
class IndexerInternalResourceUpdate extends IndexUpdate
{
    use DeprecatedCommandNotice;

    protected function initialize(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->noticeRenamedCommand(
            $output,
            'search:indexer:update-internal-resources',
            'index:update',
        );
        parent::initialize($input, $output);
    }
}
