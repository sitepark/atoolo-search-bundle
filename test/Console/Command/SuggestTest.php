<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console\Command;

use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceChannelFactory;
use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\SolrSuggestBuilder;
use Atoolo\Search\Console\Command\Suggest;
use Atoolo\Search\Dto\Search\Result\Suggestion;
use Atoolo\Search\Dto\Search\Result\SuggestResult;
use Atoolo\Search\Service\Search\SolrSuggest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(Suggest::class)]
class SuggestTest extends TestCase
{
    private CommandTester $commandTester;

    private SolrSuggest $solrSuggest;

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
            '',
            'test',
            []
        );

        $resourceChannelFactory = $this->createStub(
            ResourceChannelFactory::class
        );
        $resourceChannelFactory->method('create')
            ->willReturn($resourceChannel);
        $this->solrSuggest = $this->createStub(SolrSuggest::class);

        $command = new Suggest($resourceChannelFactory, $this->solrSuggest);

        $application = new Application([$command]);

        $command = $application->find('search:suggest');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecute(): void
    {
        $result = new SuggestResult(
            [
                new Suggestion('security', 10),
                new Suggestion('section', 5)
            ],
            10
        );
        $this->solrSuggest->method('suggest')
            ->willReturn($result);

        $this->commandTester->execute([
            'terms' => 'sec'
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Channel: WWW
============

 security (10)
 section (5)
 Query-Time: 10ms

EOF,
            $output
        );
    }

    public function testExecuteNoResult(): void
    {
        $result = new SuggestResult([], 10);
        $this->solrSuggest->method('suggest')
            ->willReturn($result);

        $this->commandTester->execute([
            'terms' => 'sec'
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Channel: WWW
============

 No suggestions found

EOF,
            $output
        );
    }
}
