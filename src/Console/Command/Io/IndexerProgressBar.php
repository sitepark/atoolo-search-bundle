<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command\Io;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Console\Command\Io\IndexerProgressBar instead
     */
    class IndexerProgressBar extends \Atoolo\Index\Console\Command\Io\IndexerProgressBar {}
}

if (!class_exists(IndexerProgressBar::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexerProgressBar::class,
        \Atoolo\Index\Console\Command\Io\IndexerProgressBar::class,
    );
    class_alias(\Atoolo\Index\Console\Command\Io\IndexerProgressBar::class, IndexerProgressBar::class);
}
