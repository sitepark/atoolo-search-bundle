<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Dto\Indexer\IndexerParameter instead
     */
    class IndexerParameter extends \Atoolo\Index\Dto\Indexer\IndexerParameter {}
}

if (!class_exists(IndexerParameter::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexerParameter::class,
        \Atoolo\Index\Dto\Indexer\IndexerParameter::class,
    );
    class_alias(\Atoolo\Index\Dto\Indexer\IndexerParameter::class, IndexerParameter::class);
}
