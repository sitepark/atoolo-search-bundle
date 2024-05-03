<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console\Command;

use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceChannelFactory;
use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\Indexer;
use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use Atoolo\Search\Service\Indexer\IndexerCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(Indexer::class)]
class IndexerTest extends TestCase
{
    private ResourceChannelFactory $resourceChannelFactory;
    private CommandTester $commandTester;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $resourceChannel = new ResourceChannel(
            '',
            'WWW',
            '',
            '',
            false,
            '',
            '',
            '',
            '',
            'test',
            []
        );

        $this->resourceChannelFactory = $this->createStub(
            ResourceChannelFactory::class
        );
        $this->resourceChannelFactory->method('create')
            ->willReturn($resourceChannel);
        $indexerA = $this->createStub(
            \Atoolo\Search\Indexer::class
        );
        $indexerA->method('enabled')
            ->willReturn(true);
        $indexerA->method('getSource')
            ->willReturn('indexer_a');
        $indexerA->method('getName')
            ->willReturn('Indexer A');
        $indexerB = $this->createStub(
            \Atoolo\Search\Indexer::class
        );
        $indexerB->method('enabled')
            ->willReturn(false);
        $indexerB->method('getSource')
            ->willReturn('indexer_b');
        $indexerB->method('getName')
            ->willReturn('Indexer B');
        $indexers = new IndexerCollection([
            $indexerA,
            $indexerB
        ]);
        $progressBar = $this->createStub(IndexerProgressBar::class);

        $command = new Indexer(
            $this->resourceChannelFactory,
            $progressBar,
            $indexers,
        );

        $application = new Application([$command]);

        $command = $application->find('search:indexer');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteAllEnabledIndexer(): void
    {
        $this->commandTester->execute([]);

        $this->commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Channel: WWW
============


Index with Indexer "Indexer A"
------------------------------



Status
------

 


EOF,
            $output
        );
    }

    public function testExecuteIndexerA(): void
    {
        $this->commandTester->execute(
            [
                '--source' => 'indexer_a'
            ]
        );

        $this->commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Channel: WWW
============


Index with Indexer "Indexer A"
------------------------------



Status
------

 


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
            \Atoolo\Search\Indexer::class
        );
        $indexer->method('enabled')
            ->willReturn(true);
        $indexers = new IndexerCollection([$indexer]);
        $progressBar = $this->createStub(
            IndexerProgressBar::class
        );
        $progressBar
            ->method('getErrors')
            ->willReturn([new \Exception('errortest')]);

        $command = new Indexer(
            $this->resourceChannelFactory,
            $progressBar,
            $indexers,
        );

        $application = new Application([$command]);

        $command = $application->find('search:indexer');
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
            \Atoolo\Search\Indexer::class
        );
        $indexer->method('enabled')
            ->willReturn(true);
        $indexers = new IndexerCollection([$indexer]);
        $progressBar = $this->createStub(
            IndexerProgressBar::class
        );
        $progressBar
            ->method('getErrors')
            ->willReturn([new \Exception('errortest')]);

        $command = new Indexer(
            $this->resourceChannelFactory,
            $progressBar,
            $indexers,
        );

        $application = new Application([$command]);

        $command = $application->find('search:indexer');
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
