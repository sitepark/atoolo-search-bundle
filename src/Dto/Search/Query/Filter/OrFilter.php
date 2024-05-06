<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

/**
 * @codeCoverageIgnore
 */
class OrFilter extends Filter
{
    /**
     * @param Filter[] $filter
     * @param string[] $tags
     */
    public function __construct(
        public readonly array $filter,
        ?string $key = null,
        array $tags = []
    ) {
        parent::__construct($key, $tags);
    }
}
