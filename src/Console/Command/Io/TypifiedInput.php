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

if (!class_exists(TypifiedInput::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        TypifiedInput::class,
        \Atoolo\Index\Console\Command\Io\TypifiedInput::class,
    );
    class_alias(\Atoolo\Index\Console\Command\Io\TypifiedInput::class, TypifiedInput::class);
}
