<?php

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Resource\Loader\StaticResourceBaseLocator;
use Atoolo\Search\Service\Indexer\LocationFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocationFinder::class)]
class LocationFinderTest extends TestCase
{
    public function testFindAll(): void
    {
        $baseLocator = new StaticResourceBaseLocator('');
    }
}
