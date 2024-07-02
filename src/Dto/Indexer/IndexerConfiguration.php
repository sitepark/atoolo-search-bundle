<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

use Atoolo\Resource\DataBag;

/**
 * @codeCoverageIgnore
 */
class IndexerConfiguration
{
    public function __construct(
        public readonly string $source,
        public readonly string $name,
        public readonly DataBag $data,
    ) {}
}
