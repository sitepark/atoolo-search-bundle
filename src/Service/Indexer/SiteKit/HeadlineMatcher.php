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

if (!class_exists(HeadlineMatcher::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        HeadlineMatcher::class,
        \Atoolo\Index\Service\Indexer\SiteKit\HeadlineMatcher::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\SiteKit\HeadlineMatcher::class, HeadlineMatcher::class);
}
