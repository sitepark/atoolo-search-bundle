<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

/**
 * @codeCoverageIgnore
 */
class FacetField extends Facet
{
    /**
     * @param string[] $terms
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        public readonly array $terms,
        array $excludeFilter = []
    ) {
        parent::__construct($key, $excludeFilter);
    }
}
