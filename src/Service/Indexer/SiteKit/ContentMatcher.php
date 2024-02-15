<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

interface ContentMatcher
{
    /**
     * @param string[] $path
     * @param array<mixed, mixed> $value
     */
    public function match(array $path, array $value): bool|string;
}
