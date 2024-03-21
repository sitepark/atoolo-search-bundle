<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Search\Service\Indexer\SolrIndexService;
use Atoolo\Search\Service\IndexName;
use Atoolo\Search\Service\SolrClientFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Solarium\Client;
use Solarium\QueryType\Server\CoreAdmin\Result\Result as CoreAdminResult;
use Solarium\QueryType\Server\CoreAdmin\Result\StatusResult;

#[CoversClass(SolrIndexService::class)]
class SolrIndexServiceTest extends TestCase
{
    private IndexName $indexName;
    private SolrIndexService $indexService;
    private SolrClientFactory&MockObject $factory;
    private Client&MockObject $client;

    public function setUp(): void
    {
        $statusResult = $this->createStub(StatusResult::class);
        $statusResult->method('getCoreName')->willReturn('test');
        $statusResultEn = $this->createStub(StatusResult::class);
        $statusResultEn->method('getCoreName')->willReturn('test-en_US');
        $response = $this->createStub(CoreAdminResult::class);
        $response->method('getStatusResults')->willReturn([
            $statusResult,
            $statusResultEn
        ]);

        $this->client = $this->createMock(Client::class);
        $this->client->method('coreAdmin')->willReturn($response);
        $this->indexName = $this->createMock(IndexName::class);
        $this->indexName->method('name')->willReturn('test', 'test-en_US');
        $this->indexName->method('names')->willReturn(['test', 'test-en_US']);
        $this->factory = $this->createMock(SolrClientFactory::class);
        $this->factory->method('create')->willReturn($this->client);
        $this->indexService = new SolrIndexService(
            $this->indexName,
            $this->factory
        );
    }
    public function testUpdater(): void
    {
        $this->client->expects($this->once())->method('createUpdate');
        $this->indexService->updater('');
    }

    public function testGetIndex(): void
    {
        $index = $this->indexService->getIndex('');
        $this->assertEquals(
            'test',
            $index,
            'Index name should be returned'
        );
    }

    public function testDeleteExcludingProcessId(): void
    {
        $this->client->expects($this->once())->method('createUpdate');
        $this->indexService->deleteExcludingProcessId('', 'test', 'test');
    }

    public function testDeleteByIdListForAllLanguages(): void
    {
        $this->client->expects($this->exactly(2))->method('createUpdate');
        $this->indexService->deleteByIdListForAllLanguages('test', ['test']);
    }

    public function testByQuery(): void
    {
        $this->client->expects($this->once())->method('createUpdate');
        $this->indexService->deleteByQuery('', 'test');
    }

    public function testCommit(): void
    {
        $this->client->expects($this->once())->method('update');
        $this->indexService->commit('');
    }

    public function testCommitForAllLanguages(): void
    {
        $this->client->expects($this->exactly(2))->method('update');
        $this->indexService->commitForAllLanguages();
    }

    public function testGetManagedIndexes(): void
    {
        $statusResult = $this->createStub(StatusResult::class);
        $statusResult->method('getCoreName')->willReturn('test');
        $response = $this->createStub(CoreAdminResult::class);
        $response->method('getStatusResults')->willReturn([$statusResult]);
        $this->client->method('coreAdmin')->willReturn($response);

        $indices = $this->indexService->getManagedIndices();

        $this->assertEquals(
            ['test', 'test-en_US'],
            $indices,
            'Indices should be returned'
        );
    }
}
