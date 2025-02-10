<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\SiteKit\LinkTextMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LinkTextMatcher::class)]
class LinkTextMatcherTest extends TestCase
{
    public function testMatcher(): void
    {
        $matcher = new LinkTextMatcher();
        $value = [
            "label" => "Link nach Google",
            "url" => "http://www.google.de",
            "external" => true,
            "newWindow" => true,
            "modelType" => "content.link.link",
        ];
        $resource = $this->createStub(Resource::class);
        $content = $matcher->match([], $value, $resource);

        $this->assertEquals('Link nach Google', $content, 'unexpected linktext');
    }

    public function testMatcherWrongModeType(): void
    {
        $matcher = new LinkTextMatcher();
        $value = [
            "label" => "Link nach Google",
            "url" => "http://www.google.de",
            "external" => true,
            "newWindow" => true,
            "modelType" => "NO-content.link.link",
        ];
        $resource = $this->createStub(Resource::class);
        $content = $matcher->match([], $value, $resource);

        $this->assertEmpty(
            $content,
            'should not find any content',
        );
    }

    public function testMatcherNoExternalLink(): void
    {
        $matcher = new LinkTextMatcher();
        $value = [
            "label" => "Link nach Google",
            "url" => "http://www.google.de",
            "external" => false,
            "newWindow" => true,
            "modelType" => "content.link.link",
        ];
        $resource = $this->createStub(Resource::class);
        $content = $matcher->match([], $value, $resource);

        $this->assertEmpty(
            $content,
            'should not find internal link text',
        );
    }
}
