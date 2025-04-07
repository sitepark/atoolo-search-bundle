<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console\Command;

use Atoolo\Resource\Factory\ResourceChannelFactory;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceTenant;
use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\Indexer;
use Atoolo\Search\Console\Command\Io\IndexerProgressBar;
use Atoolo\Search\Service\Indexer\IndexerCollection;
use Atoolo\Search\Service\Indexer\InternalResourceIndexer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(Indexer::class)]
class IndexerTest extends TestCase
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
            $resourceTanent,
        );

        $indexerA = $this->createStub(
            InternalResourceIndexer::class,
        );
        $indexerA->method('enabled')
            ->willReturn(true);
        $indexerA->method('getSource')
            ->willReturn('indexer_a');
        $indexerA->method('getName')
            ->willReturn('Indexer A');

        $indexerB = $this->createStub(
            \Atoolo\Search\Indexer::class,
        );
        $indexerB->method('enabled')
            ->willReturn(false);
        $indexerB->method('getSource')
            ->willReturn('indexer_b');
        $indexerB->method('getName')
            ->willReturn('Indexer B');

        $indexerC = $this->createStub(
            \Atoolo\Search\Indexer::class,
        );
        $indexerC->method('enabled')
            ->willReturn(true);
        $indexerC->method('getSource')
            ->willReturn('indexer_c');
        $indexerC->method('getName')
            ->willReturn('Indexer C');

        $indexers = new IndexerCollection([
            $indexerA,
            $indexerB,
            $indexerC,
        ]);

        $progressBar = $this->createStub(IndexerProgressBar::class);

        $command = new Indexer(
            $this->resourceChannel,
            $progressBar,
            $indexers,
        );

        $application = new Application([$command]);

        $command = $application->find('search:indexer');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteWithoutIndexer(): void
    {
        $resourceTanent = $this->createMock(ResourceTenant::class);
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
            '',
            'test',
            [],
            $resourceTanent,
        );
        $resourceChannelFactory = $this->createStub(
            ResourceChannelFactory::class,
        );
        $resourceChannelFactory->method('create')
            ->willReturn($resourceChannel);

        $progressBar = $this->createStub(IndexerProgressBar::class);
        $indexers = new IndexerCollection([]);
        $command = new Indexer(
            $resourceChannel,
            $progressBar,
            $indexers,
        );
        $application = new Application([$command]);

        $command = $application->find('search:indexer');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertEquals(
            Command::FAILURE,
            $commandTester->getStatusCode(),
            'command should failed',
        );

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF
Channel: WWW
============

 [ERROR] No indexer available
EOF,
            trim($output),
        );
    }
    public function testExecuteSelectIndexer(): void
    {

        $this->commandTester->setInputs(['0']);

        $this->commandTester->execute([]);

        $this->commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString(
            'You have just selected: Indexer A (source: indexer_a)',
            $output,
        );
    }

    public function testExecuteIndexerA(): void
    {
        $this->commandTester->execute(
            [
                '--source' => 'indexer_a',
            ],
        );

        $this->commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Channel: WWW
============


Index with Indexer "Indexer A" (source: indexer_a)
--------------------------------------------------



Status
------

 


EOF,
            $output,
        );
    }

    public function testExecuteIndexerAWithPath(): void
    {
        $this->commandTester->execute(
            [
                '--source' => 'indexer_a',
                'paths' => ['/path/to/file'],
            ],
        );

        $this->commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Channel: WWW
============


Index with Indexer "Indexer A" (source: indexer_a)
--------------------------------------------------



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
            \Atoolo\Search\Indexer::class,
        );
        $indexer->method('enabled')
            ->willReturn(true);
        $indexers = new IndexerCollection([$indexer]);
        $progressBar = $this->createStub(
            IndexerProgressBar::class,
        );
        $progressBar
            ->method('getErrors')
            ->willReturn([new \Exception('errortest')]);

        $command = new Indexer(
            $this->resourceChannel,
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
            'error message expected',
        );
    }


    /**
     * @throws Exception
     */
    public function testExecuteIndexWithErrorsAndStackTrace(): void
    {

        $indexer = $this->createStub(
            \Atoolo\Search\Indexer::class,
        );
        $indexer->method('enabled')
            ->willReturn(true);
        $indexers = new IndexerCollection([$indexer]);
        $progressBar = $this->createStub(
            IndexerProgressBar::class,
        );
        $progressBar
            ->method('getErrors')
            ->willReturn([new \Exception('errortest')]);

        $command = new Indexer(
            $this->resourceChannel,
            $progressBar,
            $indexers,
        );

        $application = new Application([$command]);

        $command = $application->find('search:indexer');
        $commandTester = new CommandTester($command);

        $commandTester->execute([], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);

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
