<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prints the rename notice of a command that moved to the index-bundle.
 *
 * The notice goes to stderr, so that piped output - the JSON of a document
 * dump for example - stays untouched.
 */
trait DeprecatedCommandNotice
{
    private function noticeRenamedCommand(
        OutputInterface $output,
        string $oldName,
        string $newName,
    ): void {
        trigger_deprecation(
            'atoolo/search-bundle',
            '1.18',
            'The command "%s" is deprecated, use "%s" instead.',
            $oldName,
            $newName,
        );
        $errorOutput = $output instanceof ConsoleOutputInterface
            ? $output->getErrorOutput()
            : $output;
        $errorOutput->writeln(
            '<comment>The command "' . $oldName . '" is deprecated, '
            . 'use "' . $newName . '" instead.</comment>',
        );
    }
}
