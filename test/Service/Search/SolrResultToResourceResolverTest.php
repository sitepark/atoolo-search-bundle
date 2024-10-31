<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use ArrayIterator;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Service\Search\ResourceFactory;
use Atoolo\Search\Service\Search\SolrExplainBuilder;
use Atoolo\Search\Service\Search\SolrResultToResourceResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result as SelectResult;

#[CoversClass(SolrResultToResourceResolver::class)]
class SolrResultToResourceResolverTest extends TestCase
{
    public function testLoadResourceList(): void
    {
        $document = $this->createStub(Document::class);
        $result = $this->createStub(SelectResult::class);
        $result->method('getIterator')->willReturn(
            new ArrayIterator([$document]),
        );

        $resource = $this->createStub(Resource::class);

        $resourceFactory = $this->createStub(ResourceFactory::class);
        $resourceFactory->method('accept')->willReturn(true);
        $resourceFactory->method('create')->willReturn($resource);

        $explainBuilder = $this->createStub(SolrExplainBuilder::class);

        $resolver = new SolrResultToResourceResolver([$resourceFactory], $explainBuilder);

        $resourceList = $resolver->loadResourceList(
            $result,
            ResourceLanguage::default(),
        );

        $this->assertEquals(
            [$resource],
            $resourceList,
            'unexpected resourceList',
        );
    }

    public function testLoadResourceListWithoutAcceptedFactory(): void
    {
        $document = $this->createStub(Document::class);
        $document->method('getFields')->willReturn(['url' => 'test']);
        $result = $this->createStub(SelectResult::class);
        $result->method('getIterator')->willReturn(
            new ArrayIterator([$document]),
        );

        $resourceFactory = $this->createStub(ResourceFactory::class);
        $resourceFactory->method('accept')->willReturn(false);

        $explainBuilder = $this->createStub(SolrExplainBuilder::class);

        $resolver = new SolrResultToResourceResolver([$resourceFactory], $explainBuilder);

        $resourceList = $resolver->loadResourceList(
            $result,
            ResourceLanguage::default(),
        );

        $this->assertEmpty(
            $resourceList,
            'resourceList should be empty',
        );
    }

    public function testLoadResourceListWithoutAcceptedFactoryNoUrl(): void
    {
        $document = $this->createStub(Document::class);
        $result = $this->createStub(SelectResult::class);
        $result->method('getIterator')->willReturn(
            new ArrayIterator([$document]),
        );

        $resourceFactory = $this->createStub(ResourceFactory::class);
        $resourceFactory->method('accept')->willReturn(false);

        $explainBuilder = $this->createStub(SolrExplainBuilder::class);

        $resolver = new SolrResultToResourceResolver([$resourceFactory], $explainBuilder);

        $resourceList = $resolver->loadResourceList(
            $result,
            ResourceLanguage::default(),
        );

        $this->assertEmpty(
            $resourceList,
            'resourceList should be empty',
        );
    }

    /**
     * @throws Exception
     */
    public function testWithExplain(): void
    {
        $document = $this->createStub(Document::class);
        $document->method('getFields')->willReturn(['explain' => [
            'value' => 1,
            'description' => 'test',
        ]]);
        $result = $this->createStub(SelectResult::class);
        $result->method('getIterator')->willReturn(
            new ArrayIterator([$document]),
        );

        $resourceFactory = $this->createStub(ResourceFactory::class);
        $resourceFactory->method('accept')->willReturn(true);
        $resource = new Resource(
            'location',
            'id',
            'name',
            'objectType',
            ResourceLanguage::default(),
            new DataBag([]),
        );
        $resourceFactory->method('create')->willReturn($resource);

        $explainBuilder = $this->createStub(SolrExplainBuilder::class);
        $explainBuilder->method('build')->willReturn(['score' => 1, 'type' => 'test']);

        $resolver = new SolrResultToResourceResolver([$resourceFactory], $explainBuilder);

        $resourceList = $resolver->loadResourceList(
            $result,
            ResourceLanguage::default(),
        );

        $this->assertEquals(
            ['score' => 1, 'type' => 'test'],
            $resourceList[0]->data->getArray('explain'),
            'unexpected explain',
        );
    }

    /**
     * @throws Exception
     */
    public function testWithGeoDinstance(): void
    {
        $document = $this->createStub(Document::class);
        $document->method('getFields')->willReturn(['distance' => 22]);
        $result = $this->createStub(SelectResult::class);
        $result->method('getIterator')->willReturn(
            new ArrayIterator([$document]),
        );

        $resourceFactory = $this->createStub(ResourceFactory::class);
        $resourceFactory->method('accept')->willReturn(true);
        $resource = new Resource(
            'location',
            'id',
            'name',
            'objectType',
            ResourceLanguage::default(),
            new DataBag([]),
        );
        $resourceFactory->method('create')->willReturn($resource);

        $explainBuilder = $this->createMock(SolrExplainBuilder::class);

        $resolver = new SolrResultToResourceResolver([$resourceFactory], $explainBuilder);

        $resourceList = $resolver->loadResourceList(
            $result,
            ResourceLanguage::default(),
        );

        /** @var array{geo:array{distance:float}} $base */
        $base = $resourceList[0]->data->getArray('base');

        $this->assertEquals(
            22.0,
            $base['geo']['distance'],
            'unexpected explain',
        );
    }

}
