<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

/**
 * @codeCoverageIgnore
 */
class QueryTemplateFacet extends Facet
{
    /**
     * @param array<string,mixed> $variables
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        public readonly string $query,
        public readonly array $variables,
        array $excludeFilter = [],
    ) {
        parent::__construct($key, $excludeFilter);
    }
}
