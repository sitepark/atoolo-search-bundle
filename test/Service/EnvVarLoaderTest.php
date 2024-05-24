<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service;

use Atoolo\Search\Service\EnvVarLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(EnvVarLoader::class)]
class EnvVarLoaderTest extends TestCase
{
    private string $baseDir = __DIR__ .
    '/../resources/Service/EnvVarLoader';

    private string $scriptFileNameBackup;

    public function setUp(): void
    {
        $this->scriptFileNameBackup = $_SERVER['SCRIPT_FILENAME'] ?? null;
    }
    public function tearDown(): void
    {
        unset($_SERVER['SOLR_URL']);
        $_SERVER['SCRIPT_FILENAME'] = $this->scriptFileNameBackup;
    }

    public function testLoadVarsWithExistsSolrUrl(): void
    {
        $_SERVER['SOLR_URL'] = 'test';
        $loader = new EnvVarLoader();
        $env = $loader->loadEnvVars();
        $this->assertFalse(
            isset($env['SOLR_URL']),
            'RESOURCE_ROOT should no set'
        );
    }

    public function testLoadVarsWithDotEnvNotFound(): void
    {
        $_SERVER['RESOURCE_ROOT'] = '/invalid-dir';
        $loader = new EnvVarLoader();
        $env = $loader->loadEnvVars();
        $this->assertFalse(
            isset($env['SOLR_URL']),
            'RESOURCE_ROOT should no set'
        );
    }

    public function testDetermineSolrUrlForDocker(): void
    {
        $hostDir = $this->baseDir .
            '/ies-env/data/publications/example.com/www';

        $_SERVER['RESOURCE_ROOT'] = $hostDir . '/resources';

        $loader = new EnvVarLoader($hostDir);
        $env = $loader->loadEnvVars();

        $this->assertEquals(
            [
                'SOLR_HOST' =>  'solr-test.example.com',
                'SOLR_SCHEME' => 'https',
                'SOLR_PORT' => '443',
                'SOLR_PATH' => '',
            ],
            $env,
            'unexpected env'
        );
    }
}
