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

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!class_exists(IndexerStatusState::class, false)) {
    class_alias(\Atoolo\Index\Dto\Indexer\IndexerStatusState::class, IndexerStatusState::class);
}
