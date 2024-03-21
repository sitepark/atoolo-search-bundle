<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

use Solarium\Client;
use Solarium\Core\Client\Adapter\Curl;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 */
class ServerVarSolrClientFactory implements SolrClientFactory
{
    private const IES_WEBNODE_SOLR_PORT = '8382';

    public function create(string $core): Client
    {
        $adapter = new Curl();
        /*
        $adapter->setTimeout($this->timeout);
        $adapter->setProxy($this->proxy);
        */
        $eventDispatcher = new EventDispatcher();
        $config = [
            'endpoint' => $this->getEndpointConfig($core)
        ];

        // create a client instance
        return new Client(
            $adapter,
            $eventDispatcher,
            $config
        );
    }

    /**
     * @return array<string, array{
     *     scheme: string,
     *     host: string,
     *     port: string,
     *     path: string,
     *     core: string
     * }>
     */
    private function getEndpointConfig(string $core): array
    {
        $url = $_SERVER['SOLR_URL'] ?? '';

        if (!empty($url)) {
            $url = parse_url($url);
            $scheme = $url['scheme'] ?? 'http';
            $host = $url['host'] ?? 'localhost';
            $port = (string)(
                $url['port'] ??
                (
                    $scheme === 'https' ?
                        '443' :
                        self::IES_WEBNODE_SOLR_PORT
                )
            );
            $path = $url['path'] ?? '';
        } else {
            $scheme = $_SERVER['SOLR_SCHEME'] ?? 'http';
            $host = (string)($_SERVER['SOLR_HOST'] ?? 'localhost');
            $port = $_SERVER['SOLR_PORT'] ?? self::IES_WEBNODE_SOLR_PORT;
            $path = '';
        }

        return [
            $host => [
                'scheme' => $scheme,
                'host' => $host,
                'port' => $port,
                'path' => $path,
                'core' => $core
            ]
        ];
    }
}
