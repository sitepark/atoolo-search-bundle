<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\SiteKit\NoIndexFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NoIndexFilter::class)]
class NoIndexFilterTest extends TestCase
{
    public function testAccept(): void
    {
        $resource = Resource::create([]);
        $filter = new NoIndexFilter();
        $this->assertTrue(
            $filter->accept($resource),
            "resource should be accepted",
        );
    }

    public function testAcceptWithNoIndex(): void
    {
        $resource = Resource::create([
            'noIndex' => true,
        ]);

        $filter = new NoIndexFilter();
        $this->assertFalse(
            $filter->accept($resource),
            "resource should not be accepted",
        );
    }
}
