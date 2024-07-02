<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

/**
 * @codeCoverageIgnore
 */
abstract class Facet
{
    /**
     * @param string[] $excludeFilter
     */
    public function __construct(
        public readonly string $key,
        public readonly array $excludeFilter = [],
    ) {}
}
