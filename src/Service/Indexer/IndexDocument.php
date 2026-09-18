<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\IndexDocument instead
     */
    interface IndexDocument extends \Atoolo\Index\Service\Indexer\IndexDocument {}
}

if (!interface_exists(IndexDocument::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexDocument::class,
        \Atoolo\Index\Service\Indexer\IndexDocument::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\IndexDocument::class, IndexDocument::class);
}
