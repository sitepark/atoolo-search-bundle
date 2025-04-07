<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

class QueryTemplateResolver
{
    /**
     * @param array<string,mixed> $variables
     */
    public function resolve(string $query, array $variables): string
    {
        $placeholders = array_combine(
            array_map(
                static fn(string $key) => '{' . $key . '}',
                array_keys($variables),
            ),
            array_values($variables),
        );

        return strtr($query, $placeholders);
    }
}
