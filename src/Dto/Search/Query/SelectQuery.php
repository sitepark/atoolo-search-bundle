<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;

class SelectQuery
{
    private readonly string $index;
    private readonly string $text;
    private readonly int $offset;
    private readonly int $limit;
    /**
     * @var Filter[]
     */
    private readonly array $filterList;
    /**
     * @var Facet[]
     */
    private readonly array $facetList;
    private readonly QueryDefaultOperator $queryDefaultOperator;

    /**
     * @internal
     */
    public function __construct(SelectQueryBuilder $builder)
    {
        $this->index = $builder->getIndex();
        $this->text = $builder->getText();
        $this->offset = $builder->getOffset();
        $this->limit = $builder->getLimit();
        $this->filterList = $builder->getFilterList();
        $this->facetList = $builder->getFacetList();
        $this->queryDefaultOperator = $builder->getQueryDefaultOperator();
    }

    public static function builder(): SelectQueryBuilder
    {
        return new SelectQueryBuilder();
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getOffset(): int
    {
        return $this->offset;
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
    /**
     * @return Facet[]
     */
    public function getFacetList(): array
    {
        return $this->facetList;
    }
    public function getQueryDefaultOperator(): QueryDefaultOperator
    {
        return $this->queryDefaultOperator;
    }
}
