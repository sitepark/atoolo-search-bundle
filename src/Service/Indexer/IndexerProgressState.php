<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\IndexerProgressState instead
     */
    class IndexerProgressState extends \Atoolo\Index\Service\Indexer\IndexerProgressState {}
}

if (!class_exists(IndexerProgressState::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexerProgressState::class,
        \Atoolo\Index\Service\Indexer\IndexerProgressState::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\IndexerProgressState::class, IndexerProgressState::class);
}
