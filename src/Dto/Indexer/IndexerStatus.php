<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Dto\Indexer\IndexerStatus instead
     */
    class IndexerStatus extends \Atoolo\Index\Dto\Indexer\IndexerStatus {}
}

if (!class_exists(IndexerStatus::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexerStatus::class,
        \Atoolo\Index\Dto\Indexer\IndexerStatus::class,
    );
    class_alias(\Atoolo\Index\Dto\Indexer\IndexerStatus::class, IndexerStatus::class);
}
