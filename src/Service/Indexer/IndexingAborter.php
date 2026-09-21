<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

// The declaration only serves IDEs, static analysis and the classmap; the
// alias itself is registered by src/legacy-aliases.php before any test runs.
// @codeCoverageIgnoreStart
if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\IndexingAborter instead
     */
    class IndexingAborter extends \Atoolo\Index\Service\Indexer\IndexingAborter {}
}

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!class_exists(IndexingAborter::class, false)) {
    class_alias(\Atoolo\Index\Service\Indexer\IndexingAborter::class, IndexingAborter::class);
}
// @codeCoverageIgnoreEnd
