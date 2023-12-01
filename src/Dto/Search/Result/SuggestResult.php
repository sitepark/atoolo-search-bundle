<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Result;

use ArrayIterator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<Suggestion>
 */
class SuggestResult implements IteratorAggregate
{
    public function __construct(
        private readonly array $suggestions,
        private readonly int $queryTime
    ) {
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->suggestions);
    }

    /**
     * @return Suggestion[]
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    public function getQueryTime(): int
    {
        return $this->queryTime;
    }
}
