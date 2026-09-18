<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command\Io;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Console\Command\Io\TypifiedInput instead
     */
    class TypifiedInput extends \Atoolo\Index\Console\Command\Io\TypifiedInput {}
}

// The alias is normally registered eagerly by src/legacy-aliases.php. This
// is the fallback for the case that the deprecated name is autoloaded first.
if (!class_exists(TypifiedInput::class, false)) {
    class_alias(\Atoolo\Index\Console\Command\Io\TypifiedInput::class, TypifiedInput::class);
}
