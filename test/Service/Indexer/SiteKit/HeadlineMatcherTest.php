<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer\SiteKit;

use Atoolo\Search\Service\Indexer\SiteKit\HeadlineMatcher;
use PHPUnit\Framework\TestCase;

class HeadlineMatcherTest extends TestCase
{
    public function testMatcher(): void
    {
        $matcher = new HeadlineMatcher();

        $value = [
            "headline" => "Überschrift"
        ];

        $content = $matcher->match(['items', 'model'], $value);

        $this->assertEquals('Überschrift', $content, 'unexpected headline');
    }
}
