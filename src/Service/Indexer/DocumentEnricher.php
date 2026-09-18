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

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!interface_exists(DocumentEnricher::class, false)) {
    class_alias(\Atoolo\Index\Service\Indexer\DocumentEnricher::class, DocumentEnricher::class);
}
