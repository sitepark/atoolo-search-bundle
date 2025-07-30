<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceTenant;
use Atoolo\Search\Dto\Indexer\IndexerConfiguration;
use Atoolo\Search\Service\Indexer\IndexerConfigurationLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(IndexerConfigurationLoader::class)]
class IndexerConfigurationLoaderTest extends TestCase
{
    private const RESOURCE_BASE = __DIR__ . '/../../resources/' .
        'Service/Indexer/IndexerConfigurationLoader';


    public function testExists(): void
    {
        $loader = $this->createLoader(
            self::RESOURCE_BASE . '/with-internal',
        );
        $this->assertTrue(
            $loader->exists('internal'),
            'Internal config should exist',
        );
    }

    public function testLoad(): void
    {
        $loader = $this->createLoader(
            self::RESOURCE_BASE . '/with-internal',
        );

        $config = $loader->load('internal');

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
            'unexpected config',
        );
    }

    public function testLoadNotExists(): void
    {
        $loader = $this->createLoader(
            self::RESOURCE_BASE . '/with-internal',
        );

        $config = $loader->load('not-exists');

        $expected = new IndexerConfiguration(
            'not-exists',
            'not-exists',
            new DataBag([
            ]),
        );

        $this->assertEquals(
            $expected,
            $config,
            'unexpected config',
        );
    }

    public function testLoadAll(): void
    {
        $loader = $this->createLoader(
            self::RESOURCE_BASE . '/with-internal',
        );

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
            $loader->loadAll(),
            'unexpected config',
        );
    }

    public function testLoadAllNotADirectory(): void
    {
        $loader = $this->createLoader(
            self::RESOURCE_BASE . '/not-a-directory',
        );

        $this->assertEquals(
            [],
            $loader->loadAll(),
            'should return empty array',
        );
    }

    public function testLoadAllWithConfigReturnString(): void
    {
        $loader = $this->createLoader(
            self::RESOURCE_BASE . '/return-string',
        );

        $this->expectException(RuntimeException::class);
        $loader->loadAll();
    }

    private function createLoader(
        string $configDir,
    ): IndexerConfigurationLoader {
        $resourceTanent = $this->createMock(ResourceTenant::class);
        $resourceChannel = new ResourceChannel(
            '',
            '',
            '',
            '',
            false,
            '',
            '',
            '',
            '',
            $configDir,
            '',
            [],
            new DataBag([]),
            $resourceTanent,
        );
        return new IndexerConfigurationLoader($resourceChannel);
    }
}
