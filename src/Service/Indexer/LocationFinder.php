<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\LocationFinder instead
     */
    class LocationFinder extends \Atoolo\Index\Service\Indexer\LocationFinder {}
}

if (!class_exists(LocationFinder::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        LocationFinder::class,
        \Atoolo\Index\Service\Indexer\LocationFinder::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\LocationFinder::class, LocationFinder::class);
}
