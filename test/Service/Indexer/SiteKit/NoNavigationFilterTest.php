<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer\SiteKit;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\Loader\SiteKitNavigationHierarchyLoader;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Resource\ResourceTenant;
use Atoolo\Search\Service\Indexer\SiteKit\NoNavigationFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(NoNavigationFilter::class)]
class NoNavigationFilterTest extends TestCase
{
    private SiteKitNavigationHierarchyLoader|Stub $navigationLoader;

    protected function setUp(): void
    {
        $this->navigationLoader = $this->createStub(
            SiteKitNavigationHierarchyLoader::class,
        );
    }

    public function testAcceptWhenResourcePathTypeIsNotId(): void
    {
        $resourceChannel = $this->createResourceChannel('path');
        $filter = new NoNavigationFilter($resourceChannel, $this->navigationLoader);
        $resource = $this->createStub(Resource::class);

        $this->assertTrue(
            $filter->accept($resource),
            'resource should be accepted when resourcePathType is not "id"',
        );
    }

    public function testAcceptWhenResourceIsRoot(): void
    {
        $resourceChannel = $this->createResourceChannel('id');
        $filter = new NoNavigationFilter($resourceChannel, $this->navigationLoader);
        $resource = $this->createStub(Resource::class);
        $this->navigationLoader->method('isRoot')->willReturn(true);

        $this->assertTrue(
            $filter->accept($resource),
            'resource should be accepted when it is a root resource',
        );
    }

    public function testAcceptWhenResourceHasPrimaryParent(): void
    {
        $resourceChannel = $this->createResourceChannel('id');
        $filter = new NoNavigationFilter($resourceChannel, $this->navigationLoader);
        $resource = $this->createStub(Resource::class);
        $this->navigationLoader->method('isRoot')->willReturn(false);
        $this->navigationLoader->method('getPrimaryParentLocation')
            ->willReturn(ResourceLocation::of('/parent.php'));

        $this->assertTrue(
            $filter->accept($resource),
            'resource should be accepted when it has a primary parent',
        );
    }

    public function testRejectWhenResourceHasNoPrimaryParent(): void
    {
        $resourceChannel = $this->createResourceChannel('id');
        $filter = new NoNavigationFilter($resourceChannel, $this->navigationLoader);
        $resource = $this->createStub(Resource::class);
        $this->navigationLoader->method('isRoot')->willReturn(false);
        $this->navigationLoader->method('getPrimaryParentLocation')->willReturn(null);

        $this->assertFalse(
            $filter->accept($resource),
            'resource should not be accepted when it has no primary parent and is not root',
        );
    }

    private function createResourceChannel(string $resourcePathType): ResourceChannel
    {
        $tenant = $this->createStub(ResourceTenant::class);
        return new ResourceChannel(
            '',
            '',
            '',
            '',
            false,
            '',
            '',
            '',
            '',
            '',
            '',
            [],
            new DataBag(['resourcePathType' => $resourcePathType]),
            $tenant,
        );
    }
}
