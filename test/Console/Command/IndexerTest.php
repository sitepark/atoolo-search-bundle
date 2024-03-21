<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console\Command;

use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\Indexer;
use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use Atoolo\Search\Service\Indexer\InternalResourceIndexer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(Indexer::class)]
class IndexerTest extends TestCase
{
    private CommandTester $commandTester;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $indexer = $this->createStub(
            InternalResourceIndexer::class
        );
        $progressBar = $this->createStub(IndexerProgressBar::class);

        $command = new Indexer(
            $progressBar,
            $indexer,
        );

        $application = new Application([$command]);

        $command = $application->find('atoolo:indexer');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteIndexAll(): void
    {
        $this->commandTester->execute([]);

        $this->commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Index all resources
===================


EOF,
            $output
        );
    }

    public function testExecuteIndexPath(): void
    {
        $this->commandTester->execute([
            // pass arguments to the helper
            'paths' => ['a.php', 'b.php']
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Index resource paths
====================

 * a.php
 * b.php


EOF,
            $output
        );
    }

    /**
     * @throws Exception
     */
    public function testExecuteIndexWithErrors(): void
    {

        $indexer = $this->createStub(
            InternalResourceIndexer::class
        );

        $progressBar = $this->createStub(
            IndexerProgressBar::class
        );
        $progressBar
            ->method('getErrors')
            ->willReturn([new \Exception('errortest')]);

        $command = new Indexer(
            $progressBar,
            $indexer,
        );

        $application = new Application([$command]);

        $command = $application->find('atoolo:indexer');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(
            'errortest',
            $output,
            'error message expected'
        );
    }


    /**
     * @throws Exception
     */
    public function testExecuteIndexWithErrorsAndStackTrace(): void
    {

        $indexer = $this->createStub(
            InternalResourceIndexer::class
        );

        $progressBar = $this->createStub(
            IndexerProgressBar::class
        );
        $progressBar
            ->method('getErrors')
            ->willReturn([new \Exception('errortest')]);

        $command = new Indexer(
            $progressBar,
            $indexer,
        );

        $application = new Application([$command]);

        $command = $application->find('atoolo:indexer');
        $commandTester = new CommandTester($command);

        $commandTester->execute([], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE
        ]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(
            'Exception trace',
            $output,
            'error message should contains stack trace'
        );
    }
}
