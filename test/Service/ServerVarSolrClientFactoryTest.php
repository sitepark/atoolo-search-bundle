<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service;

use Atoolo\Search\Service\ServerVarSolrClientFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarium\Core\Client\Endpoint;

#[CoversClass(ServerVarSolrClientFactory::class)]
class ServerVarSolrClientFactoryTest extends TestCase
{
    public function tearDown(): void
    {
        unset($_SERVER['SOLR_URL']);
        unset($_SERVER['SOLR_SCHEME']);
        unset($_SERVER['SOLR_HOST']);
        unset($_SERVER['SOLR_PORT']);
    }

    public function testCreateWithoutServerVars(): void
    {
        $factory = new ServerVarSolrClientFactory();
        $client = $factory->create('core');

        $expectedEndPoint = new Endpoint([
            'host' => 'localhost',
            'port' => '8382',
            'path' => '',
            'core' => 'core',
            'scheme' => 'http',
            'key' => 'localhost'
        ]);

        $this->assertEquals(
            $expectedEndPoint,
            $client->getEndpoint(),
            'unexpected endpoint configuration'
        );
    }

    public function testCreateWithUrlServerVar(): void
    {
        $_SERVER['SOLR_URL'] = 'https://solr.example.com:8983/path';

        $factory = new ServerVarSolrClientFactory();
        $client = $factory->create('core');

        $expectedEndPoint = new Endpoint([
            'host' => 'solr.example.com',
            'port' => '8983',
            'path' => '/path',
            'core' => 'core',
            'scheme' => 'https',
            'key' => 'solr.example.com'
        ]);

        $this->assertEquals(
            $expectedEndPoint,
            $client->getEndpoint(),
            'unexpected endpoint configuration'
        );
    }

    public function testCreateWithHttpUrlWithoutPortServerVar(): void
    {
        $_SERVER['SOLR_URL'] = 'http://solr';

        $factory = new ServerVarSolrClientFactory();
        $client = $factory->create('core');

        $expectedEndPoint = new Endpoint([
            'host' => 'solr',
            'port' => '8382',
            'path' => '',
            'core' => 'core',
            'scheme' => 'http',
            'key' => 'solr'
        ]);

        $this->assertEquals(
            $expectedEndPoint,
            $client->getEndpoint(),
            'unexpected endpoint configuration'
        );
    }

    public function testCreateWithHttpsUrlWithoutPortServerVar(): void
    {
        $_SERVER['SOLR_URL'] = 'https://solr';

        $factory = new ServerVarSolrClientFactory();
        $client = $factory->create('core');

        $expectedEndPoint = new Endpoint([
            'host' => 'solr',
            'port' => '443',
            'path' => '',
            'core' => 'core',
            'scheme' => 'https',
            'key' => 'solr'
        ]);

        $this->assertEquals(
            $expectedEndPoint,
            $client->getEndpoint(),
            'unexpected endpoint configuration'
        );
    }

    public function testCreateWithSchemaHostAndPortServerVar(): void
    {
        $_SERVER['SOLR_SCHEME'] = 'https';
        $_SERVER['SOLR_HOST'] = 'solr.example.com';
        $_SERVER['SOLR_PORT'] = '8983';

        $factory = new ServerVarSolrClientFactory();
        $client = $factory->create('core');

        $expectedEndPoint = new Endpoint([
            'host' => 'solr.example.com',
            'port' => '8983',
            'path' => '',
            'core' => 'core',
            'scheme' => 'https',
            'key' => 'solr.example.com'
        ]);

        $this->assertEquals(
            $expectedEndPoint,
            $client->getEndpoint(),
            'unexpected endpoint configuration'
        );
    }
}
