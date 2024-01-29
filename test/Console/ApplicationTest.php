<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console;

use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\Indexer;
use Atoolo\Search\Console\Command\SolrIndexerBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

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
            new Indexer([], $indexBuilder)
        ]);
        $command = $application->get('atoolo:indexer');
        $this->assertInstanceOf(
            Indexer::class,
            $command,
            'unexpected indexer command'
        );
    }
}
