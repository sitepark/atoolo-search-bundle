<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\SiteKit\NoIndexFilter instead
     */
    class NoIndexFilter extends \Atoolo\Index\Service\Indexer\SiteKit\NoIndexFilter {}
}

if (!class_exists(NoIndexFilter::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        NoIndexFilter::class,
        \Atoolo\Index\Service\Indexer\SiteKit\NoIndexFilter::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\SiteKit\NoIndexFilter::class, NoIndexFilter::class);
}
