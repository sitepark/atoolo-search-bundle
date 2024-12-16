<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\SiteKit\ContentMatcher;

class ContentCollector
{
    /**
     * @param iterable<ContentMatcher> $matchers
     */
    public function __construct(private readonly iterable $matchers) {}

    /**
    * @param array<mixed,mixed> $data
     */
    public function collect(array $data, Resource $resource): string
    {
        $content = $this->walk([], $data, $resource);
        return implode(' ', $content);
    }

    /**
     * @param string[] $path
     * @param array<mixed,mixed> $data
     * @return string[]
     */
    private function walk(array $path, array $data, Resource $resource): array
    {
        $contentCollections = [];
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                continue;
            }

            if (is_string($key)) {
                $path[] = $key;
            }

            $matcherContent = [];
            foreach ($this->matchers as $matcher) {
                $content = $matcher->match($path, $value, $resource);
                if (!is_string($content)) {
                    continue;
                }
                $matcherContent[] = $content;
            }
            $contentCollections[] = $matcherContent;
            $contentCollections[] = $this->walk($path, $value, $resource);

            if (is_string($key)) {
                array_pop($path);
            }
        }

        return array_merge(...$contentCollections);
    }
}
