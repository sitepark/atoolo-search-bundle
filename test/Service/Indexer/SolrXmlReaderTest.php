<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceTenant;
use Atoolo\Search\Service\Indexer\SolrXmlReader;
use Exception;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(SolrXmlReader::class)]
class SolrXmlReaderTest extends TestCase
{
    private string $resourceDir = __DIR__
        . '/../../resources/Service/Indexer/SolrXmlReader';

    private SolrXmlReader $reader;

    public function setUp(): void
    {
        $resourceTanent = $this->createMock(ResourceTenant::class);
        $this->reader = new SolrXmlReader(new ResourceChannel(
            id: '',
            name: '',
            anchor: '',
            serverName: '',
            isPreview: false,
            nature: '',
            locale: '',
            baseDir: $this->resourceDir,
            resourceDir: '',
            configDir: '',
            searchIndex: '',
            translationLocales: [],
            attributes: new DataBag([]),
            tenant: $resourceTanent,
        ));
    }

    /**
     * @throws Exception
     */
    public function testOpenWithInvalidFile(): void
    {
        $this->expectException(RuntimeException::class);
        $this->reader->open('not-exists.xml');
    }

    /**
     * @throws Exception
     */
    public function testCountWithoutOpen(): void
    {
        $this->expectException(LogicException::class);
        $this->reader->count();
    }

    /**
     * @throws Exception
     */
    public function testCount(): void
    {
        $this->reader->open('/data.xml');
        $this->assertEquals(
            1,
            $this->reader->count(),
            '1 document expected',
        );
    }

    /**
     * @throws Exception|\PHPUnit\Framework\MockObject\Exception
     */
    public function testNextWithoutOpen(): void
    {
        $this->expectException(LogicException::class);
        $this->reader->next(1);
    }

    /**
     * @throws Exception
     */
    public function testNext(): void
    {
        $this->reader->open('/data.xml');
        $docs = $this->reader->next(1);

        $expected = [
            [
                "id" => "123",
                "sp_source" => "test",
                "title" => "Test",
                "arrayfield" => ["Test1", "Test2"],
            ],
        ];

        $this->assertEquals(
            $expected,
            $docs,
            'unexpected doc',
        );
    }

    /**
     * @throws Exception
     */
    public function testNextWithInvalidXml(): void
    {
        $this->reader->open('/invalid.xml');
        $this->expectException(RuntimeException::class);
        $this->reader->next(1);
    }
}
