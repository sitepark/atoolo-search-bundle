<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer\SiteKit;

use Atoolo\Search\Service\Indexer\SiteKit\QuoteSectionMatcher;
use PHPUnit\Framework\TestCase;

class QuoteSectionMatcherTest extends TestCase
{
    public function testMatcher(): void
    {
        $matcher = new QuoteSectionMatcher();

        $value = [
            "type" => "quote",
            "model" => [
                "quote" => "Quote-Text",
                "citation" => "Citation"
            ]
        ];

        $content = $matcher->match(['items'], $value);

        $this->assertEquals(
            'Quote-Text Citation',
            $content,
            'unexpected quote text'
        );
    }
}
