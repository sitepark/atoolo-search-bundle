<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Result;

/**
 * @codeCoverageIgnore
 */
class SpellcheckSuggestion
{
    public function __construct(
        public readonly SpellcheckWord $original,
        public readonly SpellcheckWord $suggestion,
    ) {}
}
