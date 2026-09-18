<?php

declare(strict_types=1);

namespace Atoolo\Search;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Indexer instead
     */
    interface Indexer extends \Atoolo\Index\Indexer {}
}

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!interface_exists(Indexer::class, false)) {
    class_alias(\Atoolo\Index\Indexer::class, Indexer::class);
}
