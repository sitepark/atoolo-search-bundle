<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

/**
 * @codeCoverageIgnore
 */
class GeoLocatedFilter extends Filter
{
    public function __construct(
        public readonly bool $exists,
        ?string $key = null,
    ) {
        parent::__construct(
            $key,
            $key !== null ? [$key] : [],
        );
    }
}
