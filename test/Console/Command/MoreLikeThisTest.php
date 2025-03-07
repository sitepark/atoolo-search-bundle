<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console\Command;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceTenant;
use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\MoreLikeThis;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Service\Search\SolrMoreLikeThis;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(MoreLikeThis::class)]
class MoreLikeThisTest extends TestCase
{
    private CommandTester $commandTester;

    private SolrMoreLikeThis&Stub $solrMoreLikeThis;

    /**
     * @throws Exception
     */
    public function setUp(): void
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

        $resultResource = new Resource(
            '/test2.php',
            '',
            '',
            '',
            ResourceLanguage::default(),
            new DataBag([]),
        );
        $result = new SearchResult(
            1,
            1,
            0,
            [$resultResource],
            [],
            null,
            10,
        );
        $this->solrMoreLikeThis = $this->createStub(SolrMoreLikeThis::class);

        $command = new MoreLikeThis(
            $resourceChannel,
            $this->solrMoreLikeThis,
        );

        $application = new Application([$command]);

        $command = $application->find('search:mlt');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecute(): void
    {

        $resultResource = new Resource(
            '/test2.php',
            '',
            '',
            '',
            ResourceLanguage::default(),
            new DataBag([]),
        );
        $result = new SearchResult(
            1,
            1,
            0,
            [$resultResource],
            [],
            null,
            10,
        );
        $this->solrMoreLikeThis->method('moreLikeThis')
            ->willReturn($result);

        $this->commandTester->execute([
            'id' => '123',
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Channel: WWW
============

 1 Results:
 /test2.php
 Query-Time: 10ms

EOF,
            $output,
        );
    }

    public function testExecuteNoResult(): void
    {

        $result = new SearchResult(
            0,
            1,
            0,
            [],
            [],
            null,
            10,
        );
        $this->solrMoreLikeThis->method('moreLikeThis')
            ->willReturn($result);

        $this->commandTester->execute([
            'id' => '123',
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Channel: WWW
============

 No results found.

EOF,
            $output,
        );
    }
}
