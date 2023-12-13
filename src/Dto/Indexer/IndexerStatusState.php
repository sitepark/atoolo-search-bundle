<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

enum IndexerStatusState
{
    case UNKNOWN;
    case RUNNING;
    case INDEXED;
    case ABORTED;

    public static function valueOf(string $name): IndexerStatusState
    {
        foreach (self::cases() as $status) {
            if ($name === $status->name) {
                return $status;
            }
        }
        throw new \ValueError(
            "$name is not a valid backing value for enum " . self::class
        );
    }
}
