<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Result;

/**
 * @codeCoverageIgnore
 */
class SpellcheckWord
{
    public function __construct(
        public readonly string $word,
        public readonly int $frequency,
    ) {}
}
