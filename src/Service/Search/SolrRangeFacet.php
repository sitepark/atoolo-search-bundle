<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\Facet\Facet;

abstract class SolrRangeFacet extends Facet {
    abstract public function getStart(): string;
    abstract public function getEnd(): string;
    abstract public function getGap(): ?string;
}
