<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

/**
 * @codeCoverageIgnore
 */
class ArchiveFilter extends Filter
{
    public function __construct()
    {
        parent::__construct(
            'archive'
        );
    }
}
