<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Dto\Indexer;

use Atoolo\Search\Dto\Indexer\IndexerParameter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class IndexerParameterTest extends TestCase
{
    public function testToLowerChunkSize(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new IndexerParameter(
            'test',
            0,
            9
        );
    }
}
