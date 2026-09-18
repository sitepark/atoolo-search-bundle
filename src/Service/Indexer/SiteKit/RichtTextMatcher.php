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

if (!class_exists(RichtTextMatcher::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        RichtTextMatcher::class,
        \Atoolo\Index\Service\Indexer\SiteKit\RichtTextMatcher::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\SiteKit\RichtTextMatcher::class, RichtTextMatcher::class);
}
