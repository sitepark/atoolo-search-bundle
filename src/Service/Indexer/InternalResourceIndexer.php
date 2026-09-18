<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\InternalResourceIndexer instead
     */
    class InternalResourceIndexer extends \Atoolo\Index\Service\Indexer\InternalResourceIndexer {}
}

if (!class_exists(InternalResourceIndexer::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        InternalResourceIndexer::class,
        \Atoolo\Index\Service\Indexer\InternalResourceIndexer::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\InternalResourceIndexer::class, InternalResourceIndexer::class);
}
