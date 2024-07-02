<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

use Symfony\Component\DependencyInjection\EnvVarLoaderInterface;
use Symfony\Component\Dotenv\Dotenv;

class EnvVarLoader implements EnvVarLoaderInterface
{
    private const IES_WEBNODE_SOLR_PORT = '8382';

    /**
     * @return array{}|array{
     *     SOLR_SCHEME: string,
     *     SOLR_HOST: string,
     *     SOLR_PORT: string,
     *     SOLR_PATH: string,
     * }
     */
    public function loadEnvVars(): array
    {
        $env = [];

        $solrUrl = $_SERVER['SOLR_URL'] ?? '';
        $resourceRoot = $_SERVER['RESOURCE_ROOT'] ?? '';

        if (empty($solrUrl) && !empty($resourceRoot)) {
            $solrUrl = $this->determineSolrUrlForCliCallInDevEnvironments(
                $resourceRoot,
            );
        }
        if (is_string($solrUrl) && !empty($solrUrl)) {
            $url = parse_url($solrUrl);
            $scheme = $url['scheme'] ?? 'http';
            $host = $url['host'] ?? 'localhost';
            $port = (string) (
                $url['port'] ??
                (
                    $scheme === 'https'
                        ? '443'
                        : self::IES_WEBNODE_SOLR_PORT
                )
            );
            $path = $url['path'] ?? '';

            $env['SOLR_SCHEME'] = $scheme;
            $env['SOLR_HOST'] = $host;
            $env['SOLR_PORT'] = $port;
            $env['SOLR_PATH'] = $path;
        }

        return $env;
    }

    /**
     * In development environments, no SOLR_URL is set for the bin/console
     * calls. This can be searched for via the determined $resourceRoot for
     * the .env file of the Docker environment. This contains the
     * SERVER_BASE_NAME variable, which can be used to determine the SOLR_URL.
     *
     * If the resource root is a path below the `data/publications/`
     * directory of an IES environment such as
     * `/home/user/ies-environment/example/data/publications/example.com/www/resources`
     * for a resource layout or
     * `/home/user/ies-environment/example/data/publications/example.com/www`
     * for a DocumentRoot layout, the Solr url can be determined via the
     * `.env` file of the IES environment.
     */
    private function determineSolrUrlForCliCallInDevEnvironments(
        string $resourceRoot,
    ): ?string {

        $iesEnvBaseDirForResourceLayout = $resourceRoot . '/../../../../';
        $iesEnvBaseDirForDocumentRootLayout = $resourceRoot . '/../../../../..';
        $directories = [
            $iesEnvBaseDirForResourceLayout,
            $iesEnvBaseDirForDocumentRootLayout,
        ];

        foreach ($directories as $dir) {
            if (is_file($dir . '/.env')) {
                $dotenv = new Dotenv();
                $dotenv->load($dir . '/.env');
                return 'https://solr-' . $_ENV['SERVER_BASE_NAME'];
            }
        }

        return null;
    }
}
