<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\IndexName instead
     */
    interface IndexName extends \Atoolo\Index\Service\IndexName {}
}

if (!interface_exists(IndexName::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexName::class,
        \Atoolo\Index\Service\IndexName::class,
    );
    class_alias(\Atoolo\Index\Service\IndexName::class, IndexName::class);
}
