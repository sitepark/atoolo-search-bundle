<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\ResourceFilter;

class NoIndexFilter implements ResourceFilter
{
    public function accept(Resource $resource): bool
    {
        $noIndex = $resource->data->getBool('noIndex');
        return $noIndex !== true;
    }
}
