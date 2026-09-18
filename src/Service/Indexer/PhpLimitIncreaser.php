<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\PhpLimitIncreaser instead
     */
    class PhpLimitIncreaser extends \Atoolo\Index\Service\Indexer\PhpLimitIncreaser {}
}

if (!class_exists(PhpLimitIncreaser::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        PhpLimitIncreaser::class,
        \Atoolo\Index\Service\Indexer\PhpLimitIncreaser::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\PhpLimitIncreaser::class, PhpLimitIncreaser::class);
}
