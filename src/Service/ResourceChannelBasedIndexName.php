<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

// The declaration only serves IDEs, static analysis and the classmap; the
// alias itself is registered by src/legacy-aliases.php before any test runs.
// @codeCoverageIgnoreStart
if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\ResourceChannelBasedIndexName instead
     */
    class ResourceChannelBasedIndexName extends \Atoolo\Index\Service\ResourceChannelBasedIndexName {}
}

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!class_exists(ResourceChannelBasedIndexName::class, false)) {
    class_alias(\Atoolo\Index\Service\ResourceChannelBasedIndexName::class, ResourceChannelBasedIndexName::class);
}
// @codeCoverageIgnoreEnd
