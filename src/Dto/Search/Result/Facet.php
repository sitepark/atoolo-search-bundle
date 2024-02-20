<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Result;

class Facet
{
    public function __construct(
        public readonly string $key,
        public readonly int $hits
    ) {
    }
}
