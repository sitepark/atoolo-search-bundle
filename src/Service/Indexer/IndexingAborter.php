<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\IndexingAborter instead
     */
    class IndexingAborter extends \Atoolo\Index\Service\Indexer\IndexingAborter {}
}

if (!class_exists(IndexingAborter::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexingAborter::class,
        \Atoolo\Index\Service\Indexer\IndexingAborter::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\IndexingAborter::class, IndexingAborter::class);
}
