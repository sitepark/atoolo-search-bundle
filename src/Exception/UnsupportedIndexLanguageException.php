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

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!class_exists(UnsupportedIndexLanguageException::class, false)) {
    class_alias(\Atoolo\Index\Exception\UnsupportedIndexLanguageException::class, UnsupportedIndexLanguageException::class);
}
