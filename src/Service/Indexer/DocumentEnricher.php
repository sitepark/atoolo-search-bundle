<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @template T of \Atoolo\Index\Service\Indexer\IndexDocument
     * @extends \Atoolo\Index\Service\Indexer\DocumentEnricher<T>
     *
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\DocumentEnricher instead
     */
    interface DocumentEnricher extends \Atoolo\Index\Service\Indexer\DocumentEnricher {}
}

if (!interface_exists(DocumentEnricher::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        DocumentEnricher::class,
        \Atoolo\Index\Service\Indexer\DocumentEnricher::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\DocumentEnricher::class, DocumentEnricher::class);
}
