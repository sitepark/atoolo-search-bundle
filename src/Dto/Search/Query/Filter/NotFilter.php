<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

/**
 * @codeCoverageIgnore
 */
class NotFilter extends Filter
{
    public function __construct(
        public readonly Filter $filter,
        ?string $key = null,
        array $tags = [],
    ) {
        parent::__construct($key, $tags);
    }
}
