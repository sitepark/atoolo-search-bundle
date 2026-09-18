<?php

declare(strict_types=1);

namespace Atoolo\Search\Exception;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Exception\UnsupportedIndexLanguageException instead
     */
    class UnsupportedIndexLanguageException extends \Atoolo\Index\Exception\UnsupportedIndexLanguageException {}
}

if (!class_exists(UnsupportedIndexLanguageException::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        UnsupportedIndexLanguageException::class,
        \Atoolo\Index\Exception\UnsupportedIndexLanguageException::class,
    );
    class_alias(\Atoolo\Index\Exception\UnsupportedIndexLanguageException::class, UnsupportedIndexLanguageException::class);
}
