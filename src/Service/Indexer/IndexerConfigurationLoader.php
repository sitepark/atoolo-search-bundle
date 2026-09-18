<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

if (false) { // @phpstan-ignore if.alwaysFalse
    /**
     * @deprecated since atoolo/search-bundle 1.18,
     *   use \Atoolo\Index\Service\Indexer\IndexerConfigurationLoader instead
     */
    class IndexerConfigurationLoader extends \Atoolo\Index\Service\Indexer\IndexerConfigurationLoader {}
}

if (!class_exists(IndexerConfigurationLoader::class, false)) {
    trigger_deprecation(
        'atoolo/search-bundle',
        '1.18',
        'Class "%s" is deprecated, use "%s" instead.',
        IndexerConfigurationLoader::class,
        \Atoolo\Index\Service\Indexer\IndexerConfigurationLoader::class,
    );
    class_alias(\Atoolo\Index\Service\Indexer\IndexerConfigurationLoader::class, IndexerConfigurationLoader::class);
}
