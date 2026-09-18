<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Dto\Indexer\IndexerStatusState instead
     */
    enum IndexerStatusState: string
    {
        case UNKNOWN = 'UNKNOWN';
        case PREPARING = 'PREPARING';
        case RUNNING = 'RUNNING';
        case FINISHED = 'FINISHED';
        case ABORTED = 'ABORTED';
    }
}

if (!enum_exists(IndexerStatusState::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexerStatusState::class,
        \Atoolo\Index\Dto\Indexer\IndexerStatusState::class,
    );
    class_alias(\Atoolo\Index\Dto\Indexer\IndexerStatusState::class, IndexerStatusState::class);
}
