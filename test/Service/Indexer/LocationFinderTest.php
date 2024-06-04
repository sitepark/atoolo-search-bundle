<?php

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Resource\Loader\StaticResourceBaseLocator;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Search\Service\Indexer\LocationFinder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LocationFinder::class)]
class LocationFinderTest extends TestCase
{
    private LocationFinder $locationFinder;
    protected function setUp(): void
    {
        $resourceDir = realpath(
            __DIR__ . '/../../resources/Service/Indexer/LocationFinder'
        );
        if ($resourceDir === false) {
            throw new InvalidArgumentException('basepath not found');
        }
        $resourceChannel = new ResourceChannel(
            '',
            '',
            '',
            '',
            false,
            '',
            '',
            '',
            $resourceDir,
            '',
            '',
            []
        );
        $this->locationFinder = new LocationFinder($resourceChannel);
    }

    public function testFindAll(): void
    {
        $locations = $this->locationFinder->findAll();
        $this->assertEquals(
            [
                '/a.php',
                '/b/c.php',
                '/f.pdf.media.php'
            ],
            $locations,
            'unexpected locations'
        );
    }

    public function testFindAllWithExcludes(): void
    {
        $locations = $this->locationFinder->findAll(['/b/.*']);
        $this->assertEquals(
            [
                '/a.php',
                '/f.pdf.media.php'
            ],
            $locations,
            'unexpected locations'
        );
    }

    public function testFindPathsWithFile(): void
    {
        $locations = $this->locationFinder->findPaths(
            [
                'a.php'
            ]
        );
        $this->assertEquals(
            [
                '/a.php',
            ],
            $locations,
            'unexpected locations'
        );
    }


    public function testFindPathsWithFileWithExcludes(): void
    {
        $locations = $this->locationFinder->findPaths(
            [
                '/a.php'
            ],
            ['/a.*']
        );
        $this->assertEmpty(
            $locations,
            'locations should be empty'
        );
    }

    public function testFindPathsWithDirectory(): void
    {
        $locations = $this->locationFinder->findPaths(
            [
                '/b',
            ]
        );
        $this->assertEquals(
            [
                '/b/c.php',
            ],
            $locations,
            'unexpected locations'
        );
    }

    public function testFindPathsWithDirectoryAndExcludes(): void
    {
        $locations = $this->locationFinder->findPaths(
            [
                '/b',
            ],
            ['c\..*']
        );
        $this->assertEmpty(
            $locations,
            'locations should be empty'
        );
    }
}
