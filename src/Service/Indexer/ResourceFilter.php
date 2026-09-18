<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\ResourceFilter instead
     */
    interface ResourceFilter extends \Atoolo\Index\Service\Indexer\ResourceFilter {}
}

if (!interface_exists(ResourceFilter::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        ResourceFilter::class,
        \Atoolo\Index\Service\Indexer\ResourceFilter::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\ResourceFilter::class, ResourceFilter::class);
}
