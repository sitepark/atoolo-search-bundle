<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * With this SolrClientFactory implementation, the necessary connection data
 * for the Solr client is transferred via the client constructor argument.
 * The SolrParameterClientFactory can be registered as a DependencyInjection
 * service by passing the necessary transfer arguments as parameters.
 */
class SolrParameterClientFactory implements SolrClientFactory
{
    public function create(string $core): Client
    {
        $host = 'solr-neu-isenburg-whinchat.veltrup.sitepark.de';

        $adapter = new Curl();
        $adapter->setTimeout(30);
        //$adapter->setProxy('http://localhost:8889');
        $eventDispatcher = new EventDispatcher();
        $config = [
            'endpoint' => [
                $host => [
                    'scheme' => 'https',
                    'host' => $host,
                    'port' => 443,
                    'path' => '',
                    'core' => $core,
                ]
            ]
        ];

        // create a client instance
        return new Client(
            $adapter,
            $eventDispatcher,
            $config
        );
    }
}
