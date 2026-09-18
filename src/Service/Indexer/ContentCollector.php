<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\ContentCollector instead
     */
    class ContentCollector extends \Atoolo\Index\Service\Indexer\ContentCollector {}
}

if (!class_exists(ContentCollector::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        ContentCollector::class,
        \Atoolo\Index\Service\Indexer\ContentCollector::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\ContentCollector::class, ContentCollector::class);
}
