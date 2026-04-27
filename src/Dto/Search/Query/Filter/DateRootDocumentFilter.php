<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

/**
 * @codeCoverageIgnore
 */
class DateRootDocumentFilter extends FieldFilter
{
    public function __construct(
        ?string $key = null,
    ) {
        parent::__construct(
            ['*'],
            $key,
        );
    }
}
