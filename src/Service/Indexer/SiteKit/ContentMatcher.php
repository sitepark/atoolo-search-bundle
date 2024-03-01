<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

/**
 * The `ContentMatcher` interface is implemented in order to extract from the
 * content structure of resources to extract the content that is relevant
 * for the relevant for the `content` field of the search index.
 */
interface ContentMatcher
{
    /**
     * @param string[] $path
     * @param array<mixed, mixed> $value
     */
    public function match(array $path, array $value): bool|string;
}
