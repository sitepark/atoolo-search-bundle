<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\IndexerCollection instead
     */
    class IndexerCollection extends \Atoolo\Index\Service\Indexer\IndexerCollection {}
}

if (!class_exists(IndexerCollection::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexerCollection::class,
        \Atoolo\Index\Service\Indexer\IndexerCollection::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\IndexerCollection::class, IndexerCollection::class);
}
