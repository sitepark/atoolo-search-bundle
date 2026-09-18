<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\SiteKit\HeadlineMatcher instead
     */
    class HeadlineMatcher extends \Atoolo\Index\Service\Indexer\SiteKit\HeadlineMatcher {}
}

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!class_exists(HeadlineMatcher::class, false)) {
    class_alias(\Atoolo\Index\Service\Indexer\SiteKit\HeadlineMatcher::class, HeadlineMatcher::class);
}
