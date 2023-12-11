<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Throwable;

interface IndexerProgressHandler
{
    public function start(int $total): void;
    public function advance(int $step): void;
    public function error(Throwable $throwable): void;
    public function finish(): void;
    public function getStatus(): IndexerStatus;
}
