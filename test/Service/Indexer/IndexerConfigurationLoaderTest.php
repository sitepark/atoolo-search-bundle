<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\ResourceBaseLocator;
use Atoolo\Search\Dto\Indexer\IndexerConfiguration;
use Atoolo\Search\Service\Indexer\IndexerConfigurationLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(IndexerConfigurationLoader::class)]
class IndexerConfigurationLoaderTest extends TestCase
{
    private const RESOURCE_BASE = __DIR__ . '/../../resources/' .
        'Service/Indexer/IndexerConfigurationLoader';
    private IndexerConfigurationLoader $loader;

    private ResourceBaseLocator&Stub $resourceBaseLocator;

    public function setUp(): void
    {
        $this->resourceBaseLocator = $this->createStub(
            ResourceBaseLocator::class
        );
        $this->loader = new IndexerConfigurationLoader(
            $this->resourceBaseLocator
        );
    }

    public function testExists(): void
    {
        $this->resourceBaseLocator->method('locate')
            ->willReturn(self::RESOURCE_BASE . '/with-internal');
        $this->assertTrue(
            $this->loader->exists('internal'),
            'Internal config should exist'
        );
    }

    public function testLoad(): void
    {
        $this->resourceBaseLocator->method('locate')
            ->willReturn(self::RESOURCE_BASE . '/with-internal');

        $config = $this->loader->load('internal');

        $expected = new IndexerConfiguration(
            'internal',
            'Internal Indexer',
            new DataBag([
                'cleanupThreshold' => 1000,
                'chunkSize' => 500,
            ]),
        );

        $this->assertEquals(
            $expected,
            $config,
            'unexpected config'
        );
    }

    public function testLoadNotExists(): void
    {
        $this->resourceBaseLocator->method('locate')
            ->willReturn(self::RESOURCE_BASE . '/with-internal');

        $config = $this->loader->load('not-exists');

        $expected = new IndexerConfiguration(
            'not-exists',
            'not-exists',
            new DataBag([
            ]),
        );

        $this->assertEquals(
            $expected,
            $config,
            'unexpected config'
        );
    }

    public function testLoadAll(): void
    {
        $this->resourceBaseLocator->method('locate')
            ->willReturn(self::RESOURCE_BASE . '/with-internal');

        $expected = new IndexerConfiguration(
            'internal',
            'Internal Indexer',
            new DataBag([
                'cleanupThreshold' => 1000,
                'chunkSize' => 500,
            ]),
        );

        $this->assertEquals(
            [$expected],
            $this->loader->loadAll(),
            'unexpected config'
        );
    }

    public function testLoadAllNotADirectory(): void
    {
        $this->resourceBaseLocator->method('locate')
            ->willReturn(self::RESOURCE_BASE . '/not-a-directory');

        $this->assertEquals(
            [],
            $this->loader->loadAll(),
            'should return empty array'
        );
    }

    public function testLoadAllWithConfigReturnString(): void
    {
        $this->resourceBaseLocator->method('locate')
            ->willReturn(self::RESOURCE_BASE . '/return-string');

        $this->expectException(RuntimeException::class);
        $this->loader->loadAll();
    }
}
