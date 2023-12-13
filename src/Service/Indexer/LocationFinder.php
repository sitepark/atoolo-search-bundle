<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Symfony\Component\Finder\Finder;

class LocationFinder
{
    private readonly string $basePath;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * @return string[]
     */
    public function findAll(): array
    {

        $finder = new Finder();
        $finder->in($this->basePath);
        $finder->name('*.php');
        $finder->files();

        $pathList = [];
        foreach ($finder as $file) {
            $pathList[] = $this->toRelativePath($file->getPathname());
        }

        return $pathList;
    }

    /**
     * @param string[] $directories
     */
    public function findPaths(array $paths): array
    {
        $pathList = [];

        $directories = [];

        $finder = new Finder();
        foreach ($paths as $path) {
            $absolutePath = $this->basePath . '/' . $path;
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
            $finder->in($this->basePath . '/' . $directory);
        }
        $finder->name('*.php');
        $finder->files();

        foreach ($finder as $file) {
            $pathList[] = $this->toRelativePath($file->getPathname());
        }

        return $pathList;
    }

    private function toRelativePath(string $path): string
    {
        return substr($path, strlen($this->basePath));
    }
}
