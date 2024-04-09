<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\Sort\Criteria;

class SearchQueryBuilder
{
    private string $text = '';
    private string $lang = '';
    private int $offset = 0;
    private int $limit = 10;
    /**
     * @var Criteria[]
     */
    private array $sort = [];
    /**
     * @var array<Filter>
     */
    private array $filter = [];

    /**
     * @var array<string,Facet>
     */
    private array $facets = [];

    private QueryOperator $defaultQueryOperator =
        QueryOperator::AND;

    public function __construct()
    {
    }

    /**
     * @return $this
     */
    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    /**
     * @return $this
     */
    public function lang(string $lang): static
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * @return $this
     */
    public function offset(int $offset): static
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('offset is lower then 0');
        }
        $this->offset = $offset;
        return $this;
    }

    /**
     * @return $this
     */
    public function limit(int $limit): static
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException('limit is lower then 0');
        }
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return $this
     */
    public function sort(Criteria ...$criteriaList): static
    {
        foreach ($criteriaList as $criteria) {
            $this->sort[] = $criteria;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function filter(Filter ...$filterList): static
    {
        foreach ($filterList as $filter) {
            if ($filter->key !== null) {
                foreach ($this->filter as $existingFilter) {
                    if ($existingFilter->key === $filter->key) {
                        throw new \InvalidArgumentException(
                            'filter key "' . $filter->key .
                            '" already exists'
                        );
                    }
                }
            }
            $this->filter[] = $filter;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function facet(Facet ...$facetList): static
    {
        foreach ($facetList as $facet) {
            if (isset($this->facets[$facet->key])) {
                throw new \InvalidArgumentException(
                    'facet key "' . $facet->key .
                    '" already exists'
                );
            }
            $this->facets[$facet->key] = $facet;
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function defaultQueryOperator(
        QueryOperator $defaultQueryOperator
    ): static {
        $this->defaultQueryOperator = $defaultQueryOperator;
        return $this;
    }

    public function build(): SearchQuery
    {
        return new SearchQuery(
            text: $this->text,
            lang: $this->lang,
            offset: $this->offset,
            limit: $this->limit,
            sort: $this->sort,
            filter: $this->filter,
            facets: array_values($this->facets),
            defaultQueryOperator: $this->defaultQueryOperator
        );
    }
}
