<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

class FacetField implements Facet
{
    /**
     * @param string[] $terms
     */
    public function __construct(
        private readonly string $key,
        private readonly string $field,
        private readonly array $terms
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return string[]
     */
    public function getTerms(): array
    {
        return $this->terms;
    }
}
