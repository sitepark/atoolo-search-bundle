<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Dto\Indexer\InternalResourceIndexerEvent instead
     */
    class InternalResourceIndexerEvent extends \Atoolo\Index\Dto\Indexer\InternalResourceIndexerEvent {}
}

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!class_exists(InternalResourceIndexerEvent::class, false)) {
    class_alias(\Atoolo\Index\Dto\Indexer\InternalResourceIndexerEvent::class, InternalResourceIndexerEvent::class);
}
