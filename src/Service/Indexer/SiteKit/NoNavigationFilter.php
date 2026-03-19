<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

use Atoolo\Resource\Loader\SiteKitNavigationHierarchyLoader;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Search\Service\Indexer\ResourceFilter;

class NoNavigationFilter implements ResourceFilter
{
    public function __construct(
        private readonly ResourceChannel $resourceChannel,
        private readonly SiteKitNavigationHierarchyLoader $navigationLoader,
    ) {}

    public function accept(Resource $resource): bool
    {
        if ($this->resourceChannel->attributes->getString('resourcePathType') !== 'id') {
            return true;
        }
        if ($this->navigationLoader->isRoot($resource)) {
            return true;
        }
        return $this->navigationLoader->getPrimaryParentLocation($resource) !== null;
    }
}
