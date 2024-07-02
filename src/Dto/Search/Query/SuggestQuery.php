<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;

/**
 * In the search context, "Suggest" refers to a feature that automatically
 * makes suggestions as the user enters a search query to speed up and
 * simplify the search process.
 *
 * @codeCoverageIgnore
 */
class SuggestQuery
{
    /**
     * @param Filter[] $filter
     */
    public function __construct(
        public readonly string $text,
        public readonly ResourceLanguage $lang,
        public readonly array $filter = [],
        public readonly int $limit = 10,
        public readonly bool $archive = false,
    ) {}
}
