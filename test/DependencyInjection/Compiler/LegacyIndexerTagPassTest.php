<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\DependencyInjection\Compiler;

use Atoolo\Search\DependencyInjection\Compiler\LegacyIndexerTagPass;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(LegacyIndexerTagPass::class)]
class LegacyIndexerTagPassTest extends TestCase
{
    public function testCopiesIndexerTag(): void
    {
        $container = $this->createContainer();
        $container->setDefinition(
            'host.indexer',
            (new Definition(\stdClass::class))
                ->addTag('atoolo_search.indexer', ['priority' => 30]),
        );

        (new LegacyIndexerTagPass())->process($container);

        $this->assertEquals(
            [['priority' => 30]],
            $container->getDefinition('host.indexer')
                ->getTag('atoolo_index.indexer'),
            'legacy indexer tag should be copied including its priority',
        );
    }

    public function testCopiesContentMatcherTag(): void
    {
        $container = $this->createContainer();
        $container->setDefinition(
            'host.matcher',
            (new Definition(\stdClass::class))
                ->addTag('atoolo_search.indexer.sitekit.content_matcher'),
        );

        (new LegacyIndexerTagPass())->process($container);

        $this->assertCount(
            1,
            $container->getDefinition('host.matcher')
                ->getTag('atoolo_index.indexer.sitekit.content_matcher'),
            'legacy content matcher tag should be copied',
        );
    }

    public function testDoesNotDuplicateTag(): void
    {
        $container = $this->createContainer();
        $container->setDefinition(
            'host.indexer',
            (new Definition(\stdClass::class))
                ->addTag('atoolo_search.indexer', ['priority' => 30])
                ->addTag('atoolo_index.indexer', ['priority' => 30]),
        );

        (new LegacyIndexerTagPass())->process($container);

        $this->assertCount(
            1,
            $container->getDefinition('host.indexer')
                ->getTag('atoolo_index.indexer'),
            'tag should not be added twice',
        );
    }

    public function testLegacyParameterWins(): void
    {
        $container = $this->createContainer();
        $container->setParameter('atoolo_index.indexer.time_limit', 7200);
        $container->setParameter('atoolo_search.indexer.time_limit', 60);

        (new LegacyIndexerTagPass())->process($container);

        $this->assertEquals(
            60,
            $container->getParameter('atoolo_index.indexer.time_limit'),
            'the value set under the legacy name should win',
        );
    }

    public function testParameterIsMirroredBack(): void
    {
        $container = $this->createContainer();
        $container->setParameter('atoolo_index.indexer.memory_limit', '1G');

        (new LegacyIndexerTagPass())->process($container);

        $this->assertEquals(
            '1G',
            $container->getParameter('atoolo_search.indexer.memory_limit'),
            'reads of the legacy parameter should keep working',
        );
    }

    public function testFailsWithoutIndexBundle(): void
    {
        $container = new ContainerBuilder();

        $this->expectException(LogicException::class);
        (new LegacyIndexerTagPass())->process($container);
    }

    private function createContainer(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(
            'atoolo_index.indexer.indexer_collection',
            new Definition(\stdClass::class),
        );
        return $container;
    }
}
