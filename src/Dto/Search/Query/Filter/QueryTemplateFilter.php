<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

/**
 * @codeCoverageIgnore
 */
class QueryTemplateFilter extends Filter
{
    /**
     * @param array<string,mixed> $variables
     */
    public function __construct(
        public readonly string $query,
        public readonly array $variables,
        ?string $key = null,
    ) {
        parent::__construct(
            $key,
        );
    }
}
