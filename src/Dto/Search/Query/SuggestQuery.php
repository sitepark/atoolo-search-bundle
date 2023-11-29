<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

use Atoolo\Search\Dto\Search\Query\Filter\Filter;

class SuggestQuery
{
    /**
     * @param string[] $termList
     * @param Filter[] $filterList
     */
    public function __construct(
        private readonly string $core,
        private readonly array $termList,
        private readonly array $filterList = [],
        private readonly int $limit = 10,
        private readonly string $field = 'raw_content'
    ) {
    }

    public function getCore(): string
    {
        return $this->core;
    }
    /**
     * @return string[]
     */
    public function getTermList(): array
    {
        return $this->termList;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return Filter[]
     */
    public function getFilterList(): array
    {
        return $this->filterList;
    }
    public function getField(): string
    {
        return $this->field;
    }
}
