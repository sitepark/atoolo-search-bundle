<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\AbstractIndexer instead
     */
    abstract class AbstractIndexer extends \Atoolo\Index\Service\AbstractIndexer {}
}

if (!class_exists(AbstractIndexer::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        AbstractIndexer::class,
        \Atoolo\Index\Service\AbstractIndexer::class,
    );
    class_alias(\Atoolo\Index\Service\AbstractIndexer::class, AbstractIndexer::class);
}
