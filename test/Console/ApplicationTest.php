<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console;

use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\Indexer;
use Atoolo\Search\Console\Command\Io\IndexerProgressBarFactory;
use Atoolo\Search\Console\Command\SolrIndexerBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(Application::class)]
class ApplicationTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testConstruct(): void
    {
        $indexBuilder = $this->createStub(
            SolrIndexerBuilder::class
        );
        $application = new Application([
            new Indexer([], $indexBuilder, new IndexerProgressBarFactory())
        ]);
        $command = $application->get('atoolo:indexer');
        $this->assertInstanceOf(
            Indexer::class,
            $command,
            'unexpected indexer command'
        );
    }
}
