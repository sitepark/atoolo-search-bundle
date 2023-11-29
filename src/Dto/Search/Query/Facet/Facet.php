<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

interface Facet
{
    public function getKey(): string;
}
