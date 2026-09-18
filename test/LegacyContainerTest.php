<?php

declare(strict_types=1);

namespace Atoolo\Search\Test;

use Atoolo\Index\Service\Indexer\IndexerCollection;
use Atoolo\Index\Service\Indexer\IndexingAborter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Atoolo\Search\DependencyInjection\Compiler\LegacyIndexerTagPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Compiles a container with both bundle configurations and a host that still
 * uses the names of the search-bundle.
 */
class LegacyContainerTest extends TestCase
{
    private ContainerBuilder $container;

    /**
     * @var string[]
     */
    private array $indexerIds = [];

    public function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.cache_dir', sys_get_temp_dir());

        foreach (
            [
                'atoolo_resource.resource_channel',
                'atoolo_resource.resource_loader',
                'atoolo_resource.navigation_hierarchy_loader',
                'atoolo_search.solarium_client_factory',
                'atoolo_search.search',
                'atoolo_search.suggest',
                'atoolo_search.more_like_this',
                'logger',
            ] as $id
        ) {
            $this->container->setDefinition(
                $id,
                (new Definition(\stdClass::class))->setSynthetic(true),
            );
        }

        $indexConfig = dirname(
            (string) (new \ReflectionClass(IndexerCollection::class))
                ->getFileName(),
        ) . '/../../../config';
        $this->load($indexConfig, 'indexer.yaml');
        $this->load($indexConfig, 'commands.yaml');
        $this->load(__DIR__ . '/../config', 'indexer.yaml');
        $this->load(__DIR__ . '/../config', 'commands.yaml');

        // a host that still uses the old class name and the old tag
        $this->container->setDefinition(
            'host.aborter',
            new Definition(
                \Atoolo\Search\Service\Indexer\IndexingAborter::class,
                [sys_get_temp_dir(), 'indexing'],
            ),
        );
        $this->container->setDefinition(
            'host.indexer',
            (new Definition(LegacyHostIndexer::class))
                ->setPublic(true)
                ->addTag('atoolo_search.indexer', ['priority' => 5]),
        );
        $this->container->setAlias(
            'test.host_aborter',
            'host.aborter',
        )->setPublic(true);

        $this->container->addCompilerPass(new LegacyIndexerTagPass());
        $this->container->addCompilerPass(
            new class ($this->indexerIds) implements CompilerPassInterface {
                /**
                 * @param string[] $ids
                 */
                public function __construct(private array &$ids) {}

                public function process(ContainerBuilder $container): void
                {
                    $this->ids = array_keys(
                        $container->findTaggedServiceIds('atoolo_index.indexer'),
                    );
                }
            },
            PassConfig::TYPE_BEFORE_REMOVING,
        );
        $this->container->compile();
    }

    public function testHostClassResolvesToIndexBundle(): void
    {
        $this->assertInstanceOf(
            IndexingAborter::class,
            $this->container->get('test.host_aborter'),
            'the deprecated class name should produce the new class',
        );
    }

    public function testLegacyTaggedIndexerIsCollected(): void
    {
        $this->assertContains(
            'host.indexer',
            $this->indexerIds,
            'an indexer with the legacy tag should carry the new tag and '
            . 'therefore end up in the indexer collection',
        );
        $this->assertContains(
            'atoolo_search.indexer.internal_resource_indexer',
            $this->indexerIds,
            'the solr indexer should be registered as well',
        );
    }

    public function testLegacyParameterIsMirrored(): void
    {
        $this->assertEquals(
            $this->container->getParameter('atoolo_index.indexer.time_limit'),
            $this->container->getParameter('atoolo_search.indexer.time_limit'),
            'reads of the legacy parameter should keep working',
        );
    }

    private function load(string $dir, string $file): void
    {
        (new YamlFileLoader($this->container, new FileLocator($dir)))
            ->load($file);
    }
}
