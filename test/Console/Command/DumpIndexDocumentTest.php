<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console\Command;

use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceChannelFactory;
use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\DumpIndexDocument;
use Atoolo\Search\Service\Indexer\IndexDocumentDumper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(DumpIndexDocument::class)]
class DumpIndexDocumentTest extends TestCase
{
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

        $resourceChannelFactory = $this->createStub(
            ResourceChannelFactory::class
        );
        $resourceChannelFactory->method('create')
            ->willReturn($resourceChannel);

        $dumper = $this->createStub(IndexDocumentDumper::class);
        $dumper->method('dump')
            ->willReturn([
                ['sp_id' => '123']
            ]);

        $dumperCommand = new DumpIndexDocument(
            $resourceChannelFactory,
            $dumper
        );

        $application = new Application([
            $dumperCommand
        ]);

        $command = $application->find('search:dump-index-document');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecute(): void
    {
        $this->commandTester->execute([
            'paths' => ['test.php']
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Channel: WWW
============

{
    "sp_id": "123"
}

EOF,
            $output
        );
    }
}
