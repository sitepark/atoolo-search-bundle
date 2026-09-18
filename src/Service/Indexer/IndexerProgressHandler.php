<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\IndexerProgressHandler instead
     */
    interface IndexerProgressHandler extends \Atoolo\Index\Service\Indexer\IndexerProgressHandler {}
}

if (!interface_exists(IndexerProgressHandler::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexerProgressHandler::class,
        \Atoolo\Index\Service\Indexer\IndexerProgressHandler::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\IndexerProgressHandler::class, IndexerProgressHandler::class);
}
