<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Result;

/**
 * @codeCoverageIgnore
 */
class Spellcheck
{
    /**
     * @param array<SpellcheckSuggestion> $suggestions
     */
    public function __construct(
        public readonly array $suggestions,
        public readonly string $collation,
    ) {}
}
