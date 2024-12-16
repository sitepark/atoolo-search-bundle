<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\SiteKit\HeadlineMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HeadlineMatcher::class)]
class HeadlineMatcherTest extends TestCase
{
    public function testMatcher(): void
    {
        $matcher = new HeadlineMatcher();

        $value = [
            "headline" => "Überschrift",
        ];

        $resource = $this->createStub(Resource::class);
        $content = $matcher->match(['items', 'model'], $value, $resource);

        $this->assertEquals('Überschrift', $content, 'unexpected headline');
    }

    public function testMatcherNotMachedPathToShort(): void
    {
        $matcher = new HeadlineMatcher();

        $value = [
            "headline" => "Überschrift",
        ];

        $resource = $this->createStub(Resource::class);
        $content = $matcher->match(['model'], $value, $resource);

        $this->assertEmpty(
            $content,
            'should not find any content',
        );
    }

    public function testMatcherNotMachedNoModel(): void
    {
        $matcher = new HeadlineMatcher();

        $value = [
            "headline" => "Überschrift",
        ];

        $resource = $this->createStub(Resource::class);
        $content = $matcher->match(['items', 'modelX'], $value, $resource);

        $this->assertEmpty(
            $content,
            'should not find any content',
        );
    }
}
