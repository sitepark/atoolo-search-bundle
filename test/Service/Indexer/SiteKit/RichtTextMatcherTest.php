<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\SiteKit\RichtTextMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RichtTextMatcher::class)]
class RichtTextMatcherTest extends TestCase
{
    public function testMatcher(): void
    {
        $matcher = new RichtTextMatcher();

        $value = [
            "normalized" => true,
            "modelType" => "html.richText",
            "text" => "<p>Ein Text</p>",
        ];

        $resource = $this->createStub(Resource::class);
        $content = $matcher->match([], $value, $resource);

        $this->assertEquals('Ein Text', $content, 'unexpected content');
    }

    public function testMatcherWithSpaceBetweenTags(): void
    {
        $matcher = new RichtTextMatcher();

        $value = [
            "normalized" => true,
            "modelType" => "html.richText",
            "text" => "<div><strong>Einleitung</strong><p>Beschreibung</p></div>",
        ];

        $resource = $this->createStub(Resource::class);
        $content = $matcher->match([], $value, $resource);

        $this->assertEquals("Einleitung\n\nBeschreibung", $content, 'unexpected content');
    }

    public function testMatcherNotMatchedInvalidType(): void
    {
        $matcher = new RichtTextMatcher();

        $value = [
            "normalized" => true,
            "modelType" => "html.richTextX",
            "text" => "<p>Ein Text</p>",
        ];

        $resource = $this->createStub(Resource::class);
        $content = $matcher->match([], $value, $resource);

        $this->assertEmpty(
            $content,
            'should not find any content',
        );
    }

    public function testMatcherNotMatchedTextMissing(): void
    {
        $matcher = new RichtTextMatcher();

        $value = [
            "normalized" => true,
            "modelType" => "html.richText",
            "textX" => "<p>Ein Text</p>",
        ];

        $resource = $this->createStub(Resource::class);
        $content = $matcher->match([], $value, $resource);

        $this->assertEmpty(
            $content,
            'should not find any content',
        );
    }
}
