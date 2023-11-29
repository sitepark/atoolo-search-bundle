<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console\Command;

use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\Indexer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class IndexerTest extends TestCase
{
    public function testExecute(): void
    {
        $application = new Application([
            new Indexer()
        ]);

        $command = $application->find('atoolo:indexer');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            // pass arguments to the helper
            'resource-dir' => 'abc',

            // prefix the key with two dashes when passing options,
            // e.g: '--some-option' => 'option_value',
            // use brackets for testing array value,
            // e.g: '--some-option' => ['option_value'],
        ]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Whoa!', $output);

        // ...
    }

}
