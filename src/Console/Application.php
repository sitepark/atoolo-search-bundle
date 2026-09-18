<?php

declare(strict_types=1);

namespace Atoolo\Search\Console;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Console\Application instead
     */
    class Application extends \Atoolo\Index\Console\Application {}
}

if (!class_exists(Application::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        Application::class,
        \Atoolo\Index\Console\Application::class,
    );
    class_alias(\Atoolo\Index\Console\Application::class, Application::class);
}
