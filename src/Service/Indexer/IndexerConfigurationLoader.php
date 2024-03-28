<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\ResourceBaseLocator;
use Atoolo\Search\Dto\Indexer\IndexerConfiguration;
use RuntimeException;

class IndexerConfigurationLoader
{
    public function __construct(
        private readonly ResourceBaseLocator $resourceBaseLocator
    ) {
    }

    /**
     * @return array<IndexerConfiguration>
     */
    public function loadAll(): array
    {
        $dir = $this->resourceBaseLocator->locate() . '/indexer';
        if (!is_dir($dir)) {
            return [];
        }

        $files = glob($dir . '/*.php');
        if ($files === false) {
            return [];
        }

        $configurations = [];
        foreach ($files as $file) {
            $configurations[] = $this->load($file);
        }
        return $configurations;
    }

    private function getFile(string $source): string
    {
        return $this->resourceBaseLocator->locate() .
            '/indexer/' . $source . '.php';
    }

    public function exists(string $source): bool
    {
        $file = $this->getFile($source);
        return file_exists($file);
    }

    public function load(string $source): IndexerConfiguration
    {
        $file = $this->getFile($source);

        if (!file_exists($file)) {
            return new IndexerConfiguration(
                $source,
                $source,
                new DataBag([]),
            );
        }

        $saveErrorReporting = error_reporting();

        try {
            error_reporting(E_ERROR | E_PARSE);
            ob_start();
            $data = require $file;
            if (!is_array($data)) {
                throw new RuntimeException(
                    'The indexer configuration ' .
                    $file . ' should return an array'
                );
            }

            if (isset($data['source']) === false) {
                throw new RuntimeException(
                    'The indexer configuration ' .
                    $file . ' should have a source key'
                );
            }

            if ($data['source'] !== $source) {
                throw new RuntimeException(
                    'source key in ' . $file . ' should match the filename'
                );
            }

            return new IndexerConfiguration(
                $data['source'],
                $data['name'] ?? $data['source'],
                new DataBag($data['data'] ?? []),
            );
        } finally {
            ob_end_clean();
            error_reporting($saveErrorReporting);
        }
    }
}
