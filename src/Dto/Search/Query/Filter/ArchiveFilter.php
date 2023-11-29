<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

class ArchiveFilter extends Filter
{
    public function __construct()
    {
        parent::__construct(
            'archive',
            '-sp_archive:true'
        );
    }
}
