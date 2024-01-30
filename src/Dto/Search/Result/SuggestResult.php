<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Result;

use ArrayIterator;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int,Suggestion>
 */
class SuggestResult implements IteratorAggregate
{
    /**
     * @param Suggestion[] $suggestions
     * @param int $queryTime
     */
    public function __construct(
        private readonly array $suggestions,
        private readonly int $queryTime
    ) {
    }

    /**
     * @return ArrayIterator<int,Suggestion>
     */
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
