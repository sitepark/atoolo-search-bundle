<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console;

use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\Indexer;
use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    public function testConstruct(): void
    {
        $application = new Application([
            new Indexer()
        ]);
        $command = $application->get('atoolo:indexer');
        $this->assertInstanceOf(
            Indexer::class,
            $command,
            'unexpected indexer command'
        );
    }
}
