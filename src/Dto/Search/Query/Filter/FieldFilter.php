<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

class FieldFilter extends Filter
{
    /**
     * @var string[]
     */
    private readonly array $values;
    /**
     * @param string[] $values
     */
    public function __construct(
        string $key,
        private readonly string $field,
        string ...$values
    ) {
        if (count($values) === 0) {
            throw new \InvalidArgumentException(
                'values is an empty array'
            );
        }
        $this->values = $values;
        parent::__construct(
            $key,
            $this->toQuery(),
            [$key]
        );
    }

    /**
     * @param string[] $values
     */
    private function toQuery(): string
    {
        $filterValue = count($this->values) === 1
            ? $this->values[0]
            : '('  . implode(' ', $this->values) . ')';
        return $this->field . ':' . $filterValue;
    }

    public function exclude(): FieldFilter
    {
        $field = $this->field;
        if (!str_starts_with($field, '-')) {
            $field = '-' . $field;
        }
        return new FieldFilter(
            $this->getKey(),
            $field,
            ...$this->values
        );
    }
}
