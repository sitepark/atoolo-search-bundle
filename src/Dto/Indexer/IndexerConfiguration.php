<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

// The declaration only serves IDEs, static analysis and the classmap; the
// alias itself is registered by src/legacy-aliases.php before any test runs.
// @codeCoverageIgnoreStart
if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Dto\Indexer\IndexerConfiguration instead
     */
    class IndexerConfiguration extends \Atoolo\Index\Dto\Indexer\IndexerConfiguration {}
}

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!class_exists(IndexerConfiguration::class, false)) {
    class_alias(\Atoolo\Index\Dto\Indexer\IndexerConfiguration::class, IndexerConfiguration::class);
}
// @codeCoverageIgnoreEnd
