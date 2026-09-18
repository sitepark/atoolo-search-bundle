<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Dto\Indexer\InternalResourceIndexerEvent instead
     */
    class InternalResourceIndexerEvent extends \Atoolo\Index\Dto\Indexer\InternalResourceIndexerEvent {}
}

if (!class_exists(InternalResourceIndexerEvent::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        InternalResourceIndexerEvent::class,
        \Atoolo\Index\Dto\Indexer\InternalResourceIndexerEvent::class,
    );
    class_alias(\Atoolo\Index\Dto\Indexer\InternalResourceIndexerEvent::class, InternalResourceIndexerEvent::class);
}
