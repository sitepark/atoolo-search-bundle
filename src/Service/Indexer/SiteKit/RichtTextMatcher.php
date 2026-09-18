<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\SiteKit\RichtTextMatcher instead
     */
    class RichtTextMatcher extends \Atoolo\Index\Service\Indexer\SiteKit\RichtTextMatcher {}
}

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!class_exists(RichtTextMatcher::class, false)) {
    class_alias(\Atoolo\Index\Service\Indexer\SiteKit\RichtTextMatcher::class, RichtTextMatcher::class);
}
