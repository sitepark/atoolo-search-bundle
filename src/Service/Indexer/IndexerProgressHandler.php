<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\Resource;
use Solarium\QueryType\Update\Result;

interface IndexerProgressHandler
{
    public function start(int $total): void;
    public function advance(int $step): void;
    public function error(\Exception $exception): void;
    public function finish(): void;
}
