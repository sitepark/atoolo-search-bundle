<?php

declare(strict_types=1);

namespace Atoolo\Search\Exception;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Exception\DocumentEnrichingException instead
     */
    class DocumentEnrichingException extends \Atoolo\Index\Exception\DocumentEnrichingException {}
}

if (!class_exists(DocumentEnrichingException::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        DocumentEnrichingException::class,
        \Atoolo\Index\Exception\DocumentEnrichingException::class,
    );
    class_alias(\Atoolo\Index\Exception\DocumentEnrichingException::class, DocumentEnrichingException::class);
}
