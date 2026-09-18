<?php

declare(strict_types=1);

namespace Atoolo\Search;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Indexer instead
     */
    interface Indexer extends \Atoolo\Index\Indexer {}
}

if (!interface_exists(Indexer::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        Indexer::class,
        \Atoolo\Index\Indexer::class,
    );
    class_alias(\Atoolo\Index\Indexer::class, Indexer::class);
}
