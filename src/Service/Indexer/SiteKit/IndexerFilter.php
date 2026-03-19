<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\ResourceFilter;

class IndexerFilter implements ResourceFilter
{
    public function __construct(
        private readonly NoIndexFilter $noIndexFilter,
        private readonly NoNavigationFilter $noNavigationFilter,
    ) {}

    public function accept(Resource $resource): bool
    {
        return $this->noIndexFilter->accept($resource) && $this->noNavigationFilter->accept($resource);
    }
}
