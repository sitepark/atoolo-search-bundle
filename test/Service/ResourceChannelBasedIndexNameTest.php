<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceTenant;
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
        $resourceTanent = $this->createMock(ResourceTenant::class);
        $resourceChannel = new ResourceChannel(
            '',
            '',
            '',
            '',
            false,
            '',
            'de_DE',
            '',
            '',
            '',
            'test',
            ['en_US'],
            new DataBag([]),
            $resourceTanent,
        );

        $this->indexName = new ResourceChannelBasedIndexName(
            $resourceChannel,
        );
    }

    public function testName(): void
    {
        $this->assertEquals(
            'test',
            $this->indexName->name(ResourceLanguage::default()),
            'The default index name should be returned ' .
            'if no language is given',
        );
    }

    public function testNameWithLang(): void
    {
        $this->assertEquals(
            'test-en_US',
            $this->indexName->name(ResourceLanguage::of('en')),
            'The language-specific index name should be returned ' .
            'if a language is given',
        );
    }

    public function testNameWithEmptyLang(): void
    {
        $this->assertEquals(
            'test',
            $this->indexName->name(ResourceLanguage::of('')),
            'The default index name should be returned ' .
            'if the default language is given',
        );
    }

    public function testNameWithResourceChannelDefaultLang(): void
    {
        $this->assertEquals(
            'test',
            $this->indexName->name(ResourceLanguage::of('de')),
            'The default index name should be returned ' .
            'if the default language is given',
        );
    }

    public function testNameWithEmptyTranslationLocales(): void
    {
        $resourceTanent = $this->createMock(ResourceTenant::class);
        $resourceChannel = new ResourceChannel(
            '',
            '',
            '',
            '',
            false,
            '',
            'de_DE',
            '',
            '',
            '',
            'test',
            [],
            new DataBag([]),
            $resourceTanent,
        );

        $indexName = new ResourceChannelBasedIndexName(
            $resourceChannel,
        );

        $this->assertEquals(
            'test',
            $indexName->name(ResourceLanguage::of('it')),
            'The default index name should be returned as no translation languages are available.',
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
            'All index names should be returned',
        );
    }
}
