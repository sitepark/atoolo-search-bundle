<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

/**
 * @codeCoverageIgnore
 */
class AndFilter extends Filter
{
    /**
     * @param Filter[] $filter
     */
    public function __construct(
        public readonly array $filter,
        ?string $key = null,
        array $tags = [],
    ) {
        parent::__construct($key, $tags);
    }
}
