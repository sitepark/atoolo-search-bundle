<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

use Atoolo\Search\Dto\Search\Query\Filter\Filter;

class MoreLikeThisQuery
{
    /**
     * @param string[] $fieldList
     * @param Filter[] $filterList
     */
    public function __construct(
        private readonly string $core,
        private readonly string $location,
        private readonly array $filterList = [],
        private readonly int $limit = 5,
        private readonly array $fieldList = ['description', 'content']
    ) {
    }

    public function getCore(): string
    {
        return $this->core;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @return Filter[]
     */
    public function getFilterList(): array
    {
        return $this->filterList;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @return array<string>
     */
    public function getFieldList(): array
    {
        return $this->fieldList;
    }
}
