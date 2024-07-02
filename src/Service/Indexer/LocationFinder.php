<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\ResourceChannel;
use Symfony\Component\Finder\Finder;

/**
 * The LocationFinder searches recursively for files that represent a
 * resource. The corresponding files are
 * recognized and returned according to certain rules.
 *
 * The rules are:
 * - files must have the extension `.php
 * - files must not be located in the `WEB-IES` directory
 * - files must not have the name `.*-1015t.php.*`
 */
class LocationFinder
{
    public function __construct(
        private readonly ResourceChannel $resourceChannel,
    ) {}

    /**
     * @param string[] $excludes regular expressions to exclude paths
     * @return string[]
     */
    public function findAll(array $excludes = []): array
    {

        $finder = new Finder();
        $finder->in($this->getBasePath())->exclude('WEB-IES');
        $finder->name('*.php');
        $finder->notPath('#.*-1015t.php.*#'); // preview files
        $finder->files();

        $pathList = [];
        foreach ($finder as $file) {
            $path  = $this->toRelativePath($file->getPathname());
            if ($this->excludePath($path, $excludes)) {
                continue;
            }
            $pathList[] = $path;
        }

        sort($pathList);

        return $pathList;
    }

    /**
     * @param string[] $paths
     * @param string[] $excludes regular expressions to exclude paths
     * @return string[]
     */
    public function findPaths(array $paths, array $excludes = []): array
    {
        $pathList = [];

        $directories = [];

        $finder = new Finder();
        foreach ($paths as $path) {
            if (!str_starts_with($path, '/')) {
                $path = '/' . $path;
            }
            if ($this->excludePath($path, $excludes)) {
                continue;
            }
            $absolutePath = $this->getBasePath() . $path;
            if (is_file($absolutePath)) {
                $pathList[] = $path;
                continue;
            }
            if (is_dir($absolutePath)) {
                $directories[] = $path;
            }
        }

        if (empty($directories)) {
            return $pathList;
        }

        foreach ($directories as $directory) {
            $finder->in($this->getBasePath() . $directory);
        }
        $finder->name('*.php');
        $finder->notPath('#.*-1015t.php.*#'); // preview files
        $finder->files();

        foreach ($finder as $file) {
            $path  = $this->toRelativePath($file->getPathname());
            if ($this->excludePath($path, $excludes)) {
                continue;
            }
            $pathList[] = $path;
        }

        sort($pathList);

        return $pathList;
    }

    /**
     * @param array<string> $excludes
     */
    private function excludePath(string $path, array $excludes): bool
    {
        foreach ($excludes as $exclude) {
            if (
                preg_match(
                    '/' .
                    str_replace('/', '\/', $exclude) . '/',
                    $path,
                )
            ) {
                return true;
            }
        }
        return false;
    }

    private function getBasePath(): string
    {
        return rtrim($this->resourceChannel->resourceDir, '/');
    }

    private function toRelativePath(string $path): string
    {
        return substr($path, strlen($this->getBasePath()));
    }
}
