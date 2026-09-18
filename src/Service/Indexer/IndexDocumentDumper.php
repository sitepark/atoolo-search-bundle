<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\IndexDocumentDumper instead
     */
    class IndexDocumentDumper extends \Atoolo\Index\Service\Indexer\IndexDocumentDumper {}
}

if (!class_exists(IndexDocumentDumper::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexDocumentDumper::class,
        \Atoolo\Index\Service\Indexer\IndexDocumentDumper::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\IndexDocumentDumper::class, IndexDocumentDumper::class);
}
