<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

// The declaration only serves IDEs, static analysis and the classmap; the
// alias itself is registered by src/legacy-aliases.php before any test runs.
// @codeCoverageIgnoreStart
if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\IndexDocument instead
     */
    interface IndexDocument extends \Atoolo\Index\Service\Indexer\IndexDocument {}
}

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!interface_exists(IndexDocument::class, false)) {
    class_alias(\Atoolo\Index\Service\Indexer\IndexDocument::class, IndexDocument::class);
}
// @codeCoverageIgnoreEnd
