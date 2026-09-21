<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

// The declaration only serves IDEs, static analysis and the classmap; the
// alias itself is registered by src/legacy-aliases.php before any test runs.
// @codeCoverageIgnoreStart
if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\AbstractIndexer instead
     */
    abstract class AbstractIndexer extends \Atoolo\Index\Service\AbstractIndexer {}
}

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!class_exists(AbstractIndexer::class, false)) {
    class_alias(\Atoolo\Index\Service\AbstractIndexer::class, AbstractIndexer::class);
}
// @codeCoverageIgnoreEnd
