<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\ResourceChannelBasedIndexName instead
     */
    class ResourceChannelBasedIndexName extends \Atoolo\Index\Service\ResourceChannelBasedIndexName {}
}

if (!class_exists(ResourceChannelBasedIndexName::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        ResourceChannelBasedIndexName::class,
        \Atoolo\Index\Service\ResourceChannelBasedIndexName::class,
    );
    class_alias(\Atoolo\Index\Service\ResourceChannelBasedIndexName::class, ResourceChannelBasedIndexName::class);
}
