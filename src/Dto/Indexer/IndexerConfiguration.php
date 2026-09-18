<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Dto\Indexer\IndexerConfiguration instead
     */
    class IndexerConfiguration extends \Atoolo\Index\Dto\Indexer\IndexerConfiguration {}
}

if (!class_exists(IndexerConfiguration::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexerConfiguration::class,
        \Atoolo\Index\Dto\Indexer\IndexerConfiguration::class,
    );
    class_alias(\Atoolo\Index\Dto\Indexer\IndexerConfiguration::class, IndexerConfiguration::class);
}
