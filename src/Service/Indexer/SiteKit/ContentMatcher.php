<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\SiteKit\ContentMatcher instead
     */
    interface ContentMatcher extends \Atoolo\Index\Service\Indexer\SiteKit\ContentMatcher {}
}

if (!interface_exists(ContentMatcher::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        ContentMatcher::class,
        \Atoolo\Index\Service\Indexer\SiteKit\ContentMatcher::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\SiteKit\ContentMatcher::class, ContentMatcher::class);
}
