<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console\Command;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceTenant;
use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\IndexerInternalResourceUpdate;
use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use Atoolo\Search\Service\Indexer\InternalResourceIndexer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(IndexerInternalResourceUpdate::class)]
class IndexerInternalResourceUpdateTest extends TestCase
{
    private ResourceChannel $resourceChannel;
    private CommandTester $commandTester;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $resourceTanent = $this->createMock(ResourceTenant::class);
        $this->resourceChannel = new ResourceChannel(
            '',
            'WWW',
            '',
            '',
            false,
            '',
            '',
            '',
            '',
            '',
            'test',
            [],
            new DataBag([]),
            $resourceTanent,
        );

        $indexer = $this->createStub(
            InternalResourceIndexer::class,
        );
        $progressBar = $this->createStub(IndexerProgressBar::class);

        $command = new IndexerInternalResourceUpdate(
            $this->resourceChannel,
            $progressBar,
            $indexer,
        );

        $application = new Application([$command]);

        $command = $application->find(
            'search:indexer:update-internal-resources',
        );
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteIndexPath(): void
    {
        $this->commandTester->execute([
            // pass arguments to the helper
            'paths' => ['a.php', 'b.php'],
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Channel: WWW
============

Index resource paths with Indexer ""
------------------------------------

 * a.php
 * b.php



Status
------

 


EOF,
            $output,
        );
    }

    /**
     * @throws Exception
     */
    public function testExecuteIndexWithErrors(): void
    {

        $indexer = $this->createStub(
            InternalResourceIndexer::class,
        );
        $progressBar = $this->createStub(
            IndexerProgressBar::class,
        );
        $progressBar
            ->method('getErrors')
            ->willReturn([new \Exception('errortest')]);

        $command = new IndexerInternalResourceUpdate(
            $this->resourceChannel,
            $progressBar,
            $indexer,
        );

        $application = new Application([$command]);

        $command = $application->find(
            'search:indexer:update-internal-resources',
        );
        $commandTester = new CommandTester($command);

        $commandTester->execute([ 'paths' => ['a.php']]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(
            'errortest',
            $output,
            'error message expected',
        );
    }


    /**
     * @throws Exception
     */
    public function testExecuteIndexWithErrorsAndStackTrace(): void
    {

        $indexer = $this->createStub(
            InternalResourceIndexer::class,
        );
        $progressBar = $this->createStub(
            IndexerProgressBar::class,
        );
        $progressBar
            ->method('getErrors')
            ->willReturn([new \Exception('errortest')]);

        $command = new IndexerInternalResourceUpdate(
            $this->resourceChannel,
            $progressBar,
            $indexer,
        );

        $application = new Application([$command]);

        $command = $application->find(
            'search:indexer:update-internal-resources',
        );
        $commandTester = new CommandTester($command);

        $commandTester->execute(
            [
                'paths' => ['a.php'],
            ],
            [
                'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
            ],
        );

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString(
            'Exception trace',
            $output,
            'error message should contains stack trace',
        );
    }
}
