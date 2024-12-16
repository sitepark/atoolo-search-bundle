<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\SiteKit\QuoteSectionMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QuoteSectionMatcher::class)]
class QuoteSectionMatcherTest extends TestCase
{
    public function testMatcher(): void
    {
        $matcher = new QuoteSectionMatcher();

        $value = [
            "type" => "quote",
            "model" => [
                "quote" => "Quote-Text",
                "citation" => "Citation",
            ],
        ];

        $resource = $this->createStub(Resource::class);
        $content = $matcher->match(['items'], $value, $resource);

        $this->assertEquals(
            'Quote-Text Citation',
            $content,
            'unexpected quote text',
        );
    }
    public function testMatcherNoMatchPathToShort(): void
    {
        $matcher = new QuoteSectionMatcher();

        $value = [
            "type" => "quote",
            "model" => [
                "quote" => "Quote-Text",
                "citation" => "Citation",
            ],
        ];

        $resource = $this->createStub(Resource::class);
        $content = $matcher->match([], $value, $resource);

        $this->assertEmpty(
            $content,
            'should not find any content',
        );
    }

    public function testMatcherNoMatchNoItems(): void
    {
        $matcher = new QuoteSectionMatcher();

        $value = [
            "type" => "quote",
            "model" => [
                "quote" => "Quote-Text",
                "citation" => "Citation",
            ],
        ];

        $resource = $this->createStub(Resource::class);
        $content = $matcher->match(['itemsX'], $value, $resource);

        $this->assertEmpty(
            $content,
            'should not find any content',
        );
    }

    public function testMatcherNoMatchInvalidType(): void
    {
        $matcher = new QuoteSectionMatcher();

        $value = [
            "type" => "quoteX",
            "model" => [
                "quote" => "Quote-Text",
                "citation" => "Citation",
            ],
        ];

        $resource = $this->createStub(Resource::class);
        $content = $matcher->match(['items'], $value, $resource);

        $this->assertEmpty(
            $content,
            'should not find any content',
        );
    }

    public function testMatcherNoMatchMissingModel(): void
    {
        $matcher = new QuoteSectionMatcher();

        $value = [
            "type" => "quote",
            "modelX" => [
                "quote" => "Quote-Text",
                "citation" => "Citation",
            ],
        ];

        $resource = $this->createStub(Resource::class);
        $content = $matcher->match(['items'], $value, $resource);

        $this->assertEmpty(
            $content,
            'should not find any content',
        );
    }
}
