<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\IndexerFilter;

class NoIndexFilter implements IndexerFilter
{
    public function accept(Resource $resource): bool
    {
        $noIndex = $resource->getData()->getBool('init.noIndex');
        return $noIndex !== true;
    }
}
