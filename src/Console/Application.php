<?php

declare(strict_types=1);

namespace Atoolo\Search\Console;

// The declaration only serves IDEs, static analysis and the classmap; the
// alias itself is registered by src/legacy-aliases.php before any test runs.
// @codeCoverageIgnoreStart
if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Console\Application instead
     */
    class Application extends \Atoolo\Index\Console\Application {}
}

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!class_exists(Application::class, false)) {
    class_alias(\Atoolo\Index\Console\Application::class, Application::class);
}
// @codeCoverageIgnoreEnd
