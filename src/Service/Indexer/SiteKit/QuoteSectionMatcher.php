<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\SiteKit\QuoteSectionMatcher instead
     */
    class QuoteSectionMatcher extends \Atoolo\Index\Service\Indexer\SiteKit\QuoteSectionMatcher {}
}

if (!class_exists(QuoteSectionMatcher::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        QuoteSectionMatcher::class,
        \Atoolo\Index\Service\Indexer\SiteKit\QuoteSectionMatcher::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\SiteKit\QuoteSectionMatcher::class, QuoteSectionMatcher::class);
}
