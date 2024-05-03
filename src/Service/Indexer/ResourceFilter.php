<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\Resource;

interface ResourceFilter
{
    public function accept(Resource $resource): bool;
}
