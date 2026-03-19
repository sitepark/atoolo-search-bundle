<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\SiteKit\IndexerFilter;
use Atoolo\Search\Service\Indexer\SiteKit\NoIndexFilter;
use Atoolo\Search\Service\Indexer\SiteKit\NoNavigationFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexerFilter::class)]
class IndexerFilterTest extends TestCase
{
    private NoIndexFilter|Stub $noIndexFilter;
    private NoNavigationFilter|Stub $noNavigationFilter;
    private IndexerFilter $filter;

    protected function setUp(): void
    {
        $this->noIndexFilter = $this->createStub(NoIndexFilter::class);
        $this->noNavigationFilter = $this->createStub(NoNavigationFilter::class);
        $this->filter = new IndexerFilter(
            $this->noIndexFilter,
            $this->noNavigationFilter,
        );
    }

    public function testAcceptWhenBothFiltersAccept(): void
    {
        $resource = $this->createStub(Resource::class);
        $this->noIndexFilter->method('accept')->willReturn(true);
        $this->noNavigationFilter->method('accept')->willReturn(true);

        $this->assertTrue(
            $this->filter->accept($resource),
            'resource should be accepted when both filters accept',
        );
    }

    public function testAcceptWhenNoIndexFilterRejects(): void
    {
        $resource = $this->createStub(Resource::class);
        $this->noIndexFilter->method('accept')->willReturn(false);
        $this->noNavigationFilter->method('accept')->willReturn(true);

        $this->assertFalse(
            $this->filter->accept($resource),
            'resource should not be accepted when noIndexFilter rejects',
        );
    }

    public function testAcceptWhenNoNavigationFilterRejects(): void
    {
        $resource = $this->createStub(Resource::class);
        $this->noIndexFilter->method('accept')->willReturn(true);
        $this->noNavigationFilter->method('accept')->willReturn(false);

        $this->assertFalse(
            $this->filter->accept($resource),
            'resource should not be accepted when noNavigationFilter rejects',
        );
    }

    public function testAcceptWhenBothFiltersReject(): void
    {
        $resource = $this->createStub(Resource::class);
        $this->noIndexFilter->method('accept')->willReturn(false);
        $this->noNavigationFilter->method('accept')->willReturn(false);

        $this->assertFalse(
            $this->filter->accept($resource),
            'resource should not be accepted when both filters reject',
        );
    }
}
