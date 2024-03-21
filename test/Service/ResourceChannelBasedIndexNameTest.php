<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service;

use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceChannelFactory;
use Atoolo\Search\Service\ResourceChannelBasedIndexName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ResourceChannelBasedIndexName::class)]
class ResourceChannelBasedIndexNameTest extends TestCase
{
    private ResourceChannelBasedIndexName $indexName;

    public function setUp(): void
    {
        $resourceChannelFactory = $this->createStub(
            ResourceChannelFactory::class
        );
        $resourceChannel = new ResourceChannel(
            '',
            '',
            '',
            '',
            false,
            '',
            '',
            '',
            'test',
            ['en_US']
        );

        $resourceChannelFactory->method('create')
            ->willReturn($resourceChannel);

        $this->indexName = new ResourceChannelBasedIndexName(
            $resourceChannelFactory
        );
    }

    public function testName(): void
    {
        $this->assertEquals(
            'test',
            $this->indexName->name(''),
            'The default index name should be returned ' .
            'if no language is given'
        );
    }

    public function testNameWithLang(): void
    {
        $this->assertEquals(
            'test-en_US',
            $this->indexName->name('en'),
            'The language-specific index name should be returned ' .
            'if a language is given'
        );
    }

    public function testNameWithUnsupportedLang(): void
    {
        $this->assertEquals(
            'test',
            $this->indexName->name('it'),
            'The default index name should be returned ' .
            'if the language is not supported'
        );
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
