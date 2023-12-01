<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

class SiteFilter extends FieldFilter
{
    public function __construct(
        ?string $key,
        string ...$site
    ) {
        parent::__construct(
            $key,
            'sp_site',
            ...$site
        );
    }
}
