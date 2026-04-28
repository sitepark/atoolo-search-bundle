<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\Service\IdPathMapper;
use Atoolo\Search\Service\Search\InternalIdBasedResourceFactory;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Solarium\QueryType\Select\Result\Document;

#[CoversClass(InternalIdBasedResourceFactory::class)]
class InternalIdBasedResourceFactoryTest extends TestCase
{
    private ResourceLoader|Stub $resourceLoader;
    private IdPathMapper|Stub $idPathMapper;

    protected function setUp(): void
    {
        $this->resourceLoader = $this->createStub(ResourceLoader::class);
        $this->idPathMapper = $this->createStub(IdPathMapper::class);
    }

    public function testAcceptReturnsFalseWhenIdPathMapperIsNull(): void
    {
        $factory = new InternalIdBasedResourceFactory($this->resourceLoader, null);
        $document = $this->createStub(Document::class);

        $this->assertFalse(
            $factory->accept($document, ResourceLanguage::default()),
            'should not be accepted when idPathMapper is null',
        );
    }

    public function testAcceptReturnsTrueForInternalResource(): void
    {
        $factory = new InternalIdBasedResourceFactory(
            $this->resourceLoader,
            $this->idPathMapper,
        );
        $document = $this->createDocumentWithSource(['internal']);

        $this->assertTrue(
            $factory->accept($document, ResourceLanguage::default()),
            'should be accepted when document has sp_source "internal"',
        );
    }

    public function testAcceptReturnsFalseForNonInternalResource(): void
    {
        $factory = new InternalIdBasedResourceFactory(
            $this->resourceLoader,
            $this->idPathMapper,
        );
        $document = $this->createDocumentWithSource(['external']);

        $this->assertFalse(
            $factory->accept($document, ResourceLanguage::default()),
            'should not be accepted when document has no sp_source "internal"',
        );
    }

    public function testCreateLoadsResourceByPath(): void
    {
        $factory = new InternalIdBasedResourceFactory(
            $this->resourceLoader,
            $this->idPathMapper,
        );
        $resource = $this->createStub(Resource::class);
        $this->idPathMapper->method('pathFor')->willReturn('000/001/000');
        $this->resourceLoader->method('load')->willReturn($resource);
        $document = $this->createDocumentWithId('1000');

        $this->assertEquals(
            $resource,
            $factory->create($document, ResourceLanguage::of('de')),
            'unexpected resource loaded for plain id',
        );
    }

    public function testCreateLoadsEmbeddedMediaResource(): void
    {
        $factory = new InternalIdBasedResourceFactory(
            $this->resourceLoader,
            $this->idPathMapper,
        );
        $resource = $this->createStub(Resource::class);
        $this->idPathMapper->method('embeddedMediaPathFor')->willReturn('000/001/000-002');
        $this->resourceLoader->method('load')->willReturn($resource);
        $document = $this->createDocumentWithId('1000-2');

        $this->assertEquals(
            $resource,
            $factory->create($document, ResourceLanguage::of('de')),
            'unexpected resource loaded for embedded media id',
        );
    }

    public function testCreateThrowsWhenDocumentHasNoId(): void
    {
        $factory = new InternalIdBasedResourceFactory(
            $this->resourceLoader,
            $this->idPathMapper,
        );
        $document = $this->createStub(Document::class);
        $document->method('getFields')->willReturn([]);

        $this->expectException(LogicException::class);
        $factory->create($document, ResourceLanguage::default());
    }

    public function testCreateThrowsWhenIdPathMapperIsNull(): void
    {
        $factory = new InternalIdBasedResourceFactory($this->resourceLoader, null);
        $document = $this->createDocumentWithId('42');

        $this->expectException(LogicException::class);
        $factory->create($document, ResourceLanguage::default());
    }

    private function createDocumentWithId(string $id): Document
    {
        $document = $this->createStub(Document::class);
        $document->method('getFields')->willReturn(['id' => $id]);
        return $document;
    }

    private function createDocumentWithSource(array $sourceList): Document
    {
        $document = $this->createStub(Document::class);
        $document->method('getFields')->willReturn(['sp_source' => $sourceList]);
        return $document;
    }
}
