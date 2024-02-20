<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

enum IndexerStatusState: string
{
    case UNKNOWN = 'UNKNOWN';
    case RUNNING = 'RUNNING';
    case INDEXED = 'INDEXED';
    case ABORTED = 'ABORTED';
}
