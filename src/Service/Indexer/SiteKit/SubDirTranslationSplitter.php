<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

use Atoolo\Search\Service\Indexer\TranslationSplitter;
use Atoolo\Search\Service\Indexer\TranslationSplitterResult;

/**
 * The task of the SubDirTranslationSplitter is to receive a list of resource
 * paths and use the file path to check for which language the resource
 * must be indexed, as each language is stored in a separate index.
 */
class SubDirTranslationSplitter implements TranslationSplitter
{
    /**
     * @param string[] $pathList
     */
    public function split(array $pathList): TranslationSplitterResult
    {
        $bases = [];
        $translations = [];
        foreach ($pathList as $path) {
            $locale = $this->extractLocaleFromPath($path);
            if ($locale === 'default') {
                $bases[] = $path;
                continue;
            }
            if (!isset($translations[$locale])) {
                $translations[$locale] = [];
            }
            $translations[$locale][] = $path;
        }

        return new TranslationSplitterResult($bases, $translations);
    }

    private function extractLocaleFromPath(string $path): string
    {
        $normalizedPath = $this->normalizePath($path);
        $filename = basename($normalizedPath);
        $parentDirName = basename(dirname($normalizedPath));

        if (!str_ends_with($parentDirName, '.php.translations')) {
            return 'default';
        }

        return basename($filename, '.php');
    }

    /**
     * A path can signal to be translated into another language via
     * the URL parameter loc. For example,
     * `/dir/file.php?loc=it_IT` defines that the path
     * `/dir/file.php.translations/it_IT.php` is to be used.
     * This method translates the URL parameter into the correct path.
     */
    private function normalizePath(string $path): string
    {
        $queryString = parse_url($path, PHP_URL_QUERY);
        if (!is_string($queryString)) {
            return $path;
        }
        $urlPath = parse_url($path, PHP_URL_PATH);
        if (!is_string($urlPath)) {
            return $path;
        }
        parse_str($queryString, $params);
        if (!isset($params['loc']) || !is_string($params['loc'])) {
            return $urlPath;
        }
        $loc = $params['loc'];
        return $urlPath . '.translations/' . $loc . ".php";
    }
}
