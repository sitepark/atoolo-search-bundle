<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console\Command;

use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\Indexer;
use Atoolo\Search\Console\Command\SolrIndexerBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class IndexerTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testExecute(): void
    {
        $indexBuilder = $this->createStub(
            SolrIndexerBuilder::class
        );
        $application = new Application([
            new Indexer(
                [],
                $indexBuilder
            )
        ]);

        $command = $application->find('atoolo:indexer');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            // pass arguments to the helper
            'resource-dir' => 'abc',
            'solr-connection-url' => 'http://localhost:8080',
            'solr-core' => 'test'
        ]);

        $commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Index all resources
===================


EOF,
            $output
        );
    }
}
