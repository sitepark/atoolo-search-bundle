<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console\Command;

use Atoolo\Resource\Resource;
use Atoolo\Search\Console\Application;
use Atoolo\Search\Console\Command\Search;
use Atoolo\Search\Dto\Search\Result\Facet;
use Atoolo\Search\Dto\Search\Result\FacetGroup;
use Atoolo\Search\Dto\Search\Result\SearchResult;
use Atoolo\Search\Service\Search\SolrSelect;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(Search::class)]
class SearchTest extends TestCase
{
    private CommandTester $commandTester;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $resultResource = $this->createStub(Resource::class);
        $resultResource->method('getLocation')
            ->willReturn('/test.php');
        $result = new SearchResult(
            1,
            1,
            0,
            [$resultResource],
            [new FacetGroup(
                'objectType',
                [new Facet(
                    'content',
                    1
                )]
            )],
            10
        );
        $solrSelect = $this->createStub(SolrSelect::class);
        $solrSelect->method('select')
            ->willReturn($result);

        $command = new Search($solrSelect);

        $application = new Application([$command]);

        $command = $application->find('atoolo:search');
        $this->commandTester = new CommandTester($command);
    }

    public function testExecute(): void
    {
        $this->commandTester->execute([
            'text' => ['test', 'abc']
        ]);

        $this->commandTester->assertCommandIsSuccessful();

        $output = $this->commandTester->getDisplay();
        $this->assertEquals(
            <<<EOF

Results (1)
===========

 /test.php

Facets
======

objectType
----------

 * content (1)

 Query-Time: 10ms

EOF,
            $output
        );
    }
}
