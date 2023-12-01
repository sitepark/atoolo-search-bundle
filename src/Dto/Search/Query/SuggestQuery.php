<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

use Atoolo\Search\Dto\Search\Query\Filter\Filter;

class SuggestQuery
{
    /**
     * @param Filter[] $filter
     */
    public function __construct(
        private readonly string $index,
        private readonly string $text,
        private readonly array $filter = [],
        private readonly int $limit = 10,
        private readonly string $field = 'raw_content'
    ) {
    }

    public function getIndex(): string
    {
        return $this->index;
    }
    /**
     * @return string[]
     */
    public function getText(): string
    {
        return $this->text;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return Filter[]
     */
    public function getFilter(): array
    {
        return $this->filter;
    }
    public function getField(): string
    {
        return $this->field;
    }
}
