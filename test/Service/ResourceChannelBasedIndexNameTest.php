<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service;

use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Exception\UnsupportedIndexLanguageException;
use Atoolo\Search\Service\ResourceChannelBasedIndexName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceChannelBasedIndexName::class)]
class ResourceChannelBasedIndexNameTest extends TestCase
{
    private ResourceChannelBasedIndexName $indexName;

    public function setUp(): void
    {
        $resourceChannel = new ResourceChannel(
            '',
            '',
            '',
            '',
            false,
            '',
            '',
            '',
            '',
            'test',
            ['en_US']
        );

        $this->indexName = new ResourceChannelBasedIndexName(
            $resourceChannel
        );
    }

    public function testName(): void
    {
        $this->assertEquals(
            'test',
            $this->indexName->name(ResourceLanguage::default()),
            'The default index name should be returned ' .
            'if no language is given'
        );
    }

    public function testNameWithLang(): void
    {
        $this->assertEquals(
            'test-en_US',
            $this->indexName->name(ResourceLanguage::of('en')),
            'The language-specific index name should be returned ' .
            'if a language is given'
        );
    }

    public function testNameWithUnsupportedLang(): void
    {
        $this->expectException(UnsupportedIndexLanguageException::class);
        $this->indexName->name(ResourceLanguage::of('it'));
    }

    public function testNames(): void
    {
        $this->assertEquals(
            ['test', 'test-en_US'],
            $this->indexName->names(),
            'All index names should be returned'
        );
    }
}
