<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

interface SolrCoreName
{
    public function name(?string $locale = null): string;
}