<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

abstract class Filter
{
    /**
     * @param string[] $tags
     */
    public function __construct(
        public readonly ?string $key,
        public readonly array $tags = []
    ) {
    }

    abstract public function getQuery(): string;
}
