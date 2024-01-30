<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\Sort\Criteria;

class SelectQueryBuilder
{
    private string $index = '';
    private string $text = '';
    private int $offset = 0;
    private int $limit = 10;
    /**
     * @var Criteria[]
     */
    private array $sort = [];
    /**
     * @var array<string,Filter>
     */
    private array $filterList = [];

    /**
     * @var array<string,Facet>
     */
    private array $facetList = [];

    private QueryDefaultOperator $queryDefaultOperator =
        QueryDefaultOperator::AND;

    /**
     * @internal
     */
    public function __construct()
    {
    }

    public function index(string $index): SelectQueryBuilder
    {
        if (empty($index)) {
            throw new \InvalidArgumentException('index is empty');
        }
        $this->index = $index;
        return $this;
    }

    /**
     * @internal
     */
    public function getIndex(): string
    {
        return $this->index;
    }

    public function text(string $text): SelectQueryBuilder
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @internal
     */
    public function getText(): string
    {
        return $this->text;
    }

    public function offset(int $offset): SelectQueryBuilder
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('offset is lower then 0');
        }
        $this->offset = $offset;
        return $this;
    }

    /**
     * @internal
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    public function limit(int $limit): SelectQueryBuilder
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException('offset is lower then 0');
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * @internal
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    public function sort(Criteria ...$criteriaList): SelectQueryBuilder
    {
        foreach ($criteriaList as $criteria) {
            $this->sort[] = $criteria;
        }
        return $this;
    }

    /**
     * @internal
     * @return Criteria[]
     */
    public function getSort(): array
    {
        return $this->sort;
    }

    public function filter(Filter ...$filterList): SelectQueryBuilder
    {
        foreach ($filterList as $filter) {
            if (isset($this->filterList[$filter->getKey()])) {
                throw new \InvalidArgumentException(
                    'filter key "' . $filter->getKey() .
                            '" already exists'
                );
            }
            $this->filterList[$filter->getKey()] = $filter;
        }
        return $this;
    }

    /**
     * @internal
     * @return Filter[]
     */
    public function getFilterList(): array
    {
        return array_values($this->filterList);
    }

    public function facet(Facet ...$facetList): SelectQueryBuilder
    {
        foreach ($facetList as $facet) {
            if (isset($this->facetList[$facet->getKey()])) {
                throw new \InvalidArgumentException(
                    'facet key "' . $facet->getKey() .
                    '" already exists'
                );
            }
            $this->facetList[$facet->getKey()] = $facet;
        }
        return $this;
    }

    /**
     * @internal
     * @return Facet[]
     */
    public function getFacetList(): array
    {
        return array_values($this->facetList);
    }

    public function queryDefaultOperator(
        QueryDefaultOperator $queryDefaultOperator
    ): SelectQueryBuilder {
        $this->queryDefaultOperator = $queryDefaultOperator;
        return $this;
    }

    public function getQueryDefaultOperator(): QueryDefaultOperator
    {
        return $this->queryDefaultOperator;
    }

    public function build(): SelectQuery
    {
        if (empty($this->index)) {
            throw new \InvalidArgumentException('index is not set');
        }
        return new SelectQuery($this);
    }
}
