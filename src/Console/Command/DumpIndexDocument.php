<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Index\Console\Command\DumpIndexDocument as IndexDumpDocument;
use Atoolo\Index\Console\Command\Io\TypifiedInput;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated since atoolo/search-bundle 1.18, use `index:dump-document` of
 *   atoolo/index-bundle instead. Will be removed in 2.0.
 */
#[AsCommand(
    name: 'search:dump-index-document',
    description: 'Dump a index document (deprecated, use index:dump-document)',
)]
class DumpIndexDocument extends IndexDumpDocument
{
    use DeprecatedCommandNotice;

    /**
     * The source is pinned, so that the output of this command keeps showing
     * the Solr document, no matter which other targets are installed.
     */
    protected function getRequestedSource(TypifiedInput $input): string
    {
        return 'internal';
    }

    protected function initialize(
        InputInterface $input,
        OutputInterface $output,
    ): void {
        $this->noticeRenamedCommand(
            $output,
            'search:dump-index-document',
            'index:dump-document',
        );
        parent::initialize($input, $output);
    }
}
