<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\ResourceBaseLocator;
use Symfony\Component\Finder\Finder;

class LocationFinder
{
    public function __construct(
        private readonly ResourceBaseLocator $baseLocator
    ) {
    }

    /**
     * @return string[]
     */
    public function findAll(): array
    {

        $finder = new Finder();
        $finder->in($this->getBasePath())->exclude('WEB-IES');
        $finder->name('*.php');
        $finder->notPath('#.*-1015t.php.*#'); // preview files
        $finder->files();

        $pathList = [];
        foreach ($finder as $file) {
            $pathList[] = $this->toRelativePath($file->getPathname());
        }

        return $pathList;
    }

    /**
     * @param string[] $paths
     * @return string[]
     */
    public function findPaths(array $paths): array
    {
        $pathList = [];

        $directories = [];

        $finder = new Finder();
        foreach ($paths as $path) {
            if (!str_starts_with($path, '/')) {
                $path = '/' . $path;
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
            $pathList[] = $this->toRelativePath($file->getPathname());
        }

        return $pathList;
    }

    private function getBasePath(): string
    {
        return rtrim($this->baseLocator->locate(), '/');
    }

    private function toRelativePath(string $path): string
    {
        return substr($path, strlen($this->getBasePath()));
    }
}
