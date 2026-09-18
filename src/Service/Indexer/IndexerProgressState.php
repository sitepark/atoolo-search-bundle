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

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!class_exists(IndexerProgressState::class, false)) {
    class_alias(\Atoolo\Index\Service\Indexer\IndexerProgressState::class, IndexerProgressState::class);
}
