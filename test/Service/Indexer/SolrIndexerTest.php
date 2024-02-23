<?php

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Dto\Indexer\IndexerParameter;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use Atoolo\Search\Service\Indexer\IndexingAborter;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use Atoolo\Search\Service\Indexer\LocationFinder;
use Atoolo\Search\Service\Indexer\SiteKit\SubDirTranslationSplitter;
use Atoolo\Search\Service\Indexer\SolrIndexer;
use Atoolo\Search\Service\Indexer\TranslationSplitter;
use Atoolo\Search\Service\SolrClientFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Solarium\Client;
use Solarium\QueryType\Server\CoreAdmin\Result\Result as CoreAdminResult;
use Solarium\QueryType\Server\CoreAdmin\Result\StatusResult;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;
use Solarium\QueryType\Update\Result as UpdateResult;

#[CoversClass(SolrIndexer::class)]
class SolrIndexerTest extends TestCase
{
    private array $availableIndexes = ['test', 'test-en_US'];

    private ResourceLoader&Stub $resourceLoader;

    private IndexerProgressHandler&MockObject $indexerProgressHandler;

    private SolrIndexer $indexer;

    private Client $solrClient;

    private LocationFinder $finder;

    private UpdateQuery $updateQuery;

    private UpdateResult $updateResult;

    private IndexingAborter&Stub $aborter;

    private DocumentEnricher&MockObject $documentEnricher;

    private TranslationSplitter $translationSplitter;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->indexerProgressHandler = $this->createMock(
            IndexerProgressHandler::class
        );
        $this->finder = $this->createMock(LocationFinder::class);
        $this->documentEnricher = $this->createMock(DocumentEnricher::class);
        $this->translationSplitter = new SubDirTranslationSplitter();
        $this->resourceLoader = $this->createStub(ResourceLoader::class);
        $this->resourceLoader->method('load')
            ->willReturnCallback(function ($path) {
                $resource = $this->createStub(Resource::class);
                $resource->method('getLocation')
                    ->willReturn($path);
                return $resource;
            });
        $solrClientFactory = $this->createStub(SolrClientFactory::class);
        $this->updateResult = $this->createStub(UpdateResult::class);
        $this->solrClient = $this->createMock(Client::class);
        $this->updateQuery = $this->createMock(UpdateQuery::class);
        $this->updateQuery->method('createDocument')
            ->willReturn($this->createStub(IndexSchema2xDocument::class));
        $coreAdminResult = $this->createStub(CoreAdminResult::class);
        $coreAdminResult->method('getStatusResults')
            ->willReturnCallback(function () {
                $results = [];
                foreach($this->availableIndexes as $index) {
                    $result = $this->createStub(StatusResult::class);
                    $result->method('getCoreName')
                        ->willReturn($index);
                    $results[] = $result;
                }
                return $results;
            });
        $this->solrClient->method('createUpdate')
            ->willReturn($this->updateQuery);
        $this->solrClient->method('update')
            ->willReturn($this->updateResult);
        $this->solrClient->method('coreAdmin')
            ->willReturn($coreAdminResult);
        $solrClientFactory->method('create')
            ->willReturn($this->solrClient);
        $this->aborter =  $this->createMock(IndexingAborter::class);

        $this->indexer = new SolrIndexer(
            [ $this->documentEnricher ],
            $this->indexerProgressHandler,
            $this->finder,
            $this->resourceLoader,
            $this->translationSplitter,
            $solrClientFactory,
            $this->aborter,
            'test'
        );
    }

    public function testAbort(): void
    {
        $this->aborter->expects($this->once())
            ->method('abort')
            ->with('test');

        $this->indexer->abort('test');
    }

    public function testRemove(): void
    {
        $this->solrClient->expects($this->exactly(2))
            ->method('update');

        $this->indexer->remove('test', ['123']);
    }

    public function testRemoveEmpty(): void
    {
        $this->solrClient->expects($this->exactly(0))
            ->method('update');

        $this->indexer->remove('test', []);
    }

    public function testIndexAllWithChunks(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
                '/a/b.php.translations/en_US.php',
                '/a/c.php',
                '/a/c.php.translations/fr_FR.php',
                '/a/d.php',
                '/a/e.php',
                '/a/f.php',
                '/a/g.php',
                '/a/h.php',
                '/a/i.php',
                '/a/j.php',
                '/a/k.php',
                '/a/l.php',
                '/a/error.php'
            ]);

        $this->updateResult->method('getStatus')
            ->willReturn(0);

        $this->documentEnricher->method('isIndexable')
            ->willReturn(true);

        $this->documentEnricher
            ->method('enrichDocument')
            ->willReturnCallback(function ($resource, $doc) {
                if ($resource->getLocation() === '/a/error.php') {
                    throw new \Exception('test');
                }
                return $doc;
            });

        $addDocumentsCalls = 0;
        $this->updateQuery->expects($this->exactly(3))
            ->method('addDocuments')
            ->willReturnCallback(
                function ($documents) use (&$addDocumentsCalls) {
                    if ($addDocumentsCalls === 0) {
                        $this->assertCount(
                            10,
                            $documents,
                            "10 documents expected"
                        );
                    } elseif ($addDocumentsCalls === 1) {
                        $this->assertCount(
                            1,
                            $documents,
                            "1 document expected"
                        );
                    }
                    $addDocumentsCalls++;
                    return $this->updateQuery;
                }
            );

        $parameter = new IndexerParameter(
            'test',
            10,
            10
        );

        $this->indexerProgressHandler->expects($this->exactly(2))
            ->method('error');

        $this->indexer->index($parameter);
    }

    public function testIndexSkipResource(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
                '/a/c.php'
            ]);

        $this->updateResult->method('getStatus')
            ->willReturn(0);

        $this->documentEnricher->method('isIndexable')
            ->willReturnCallback(function (Resource $resource) {
                $location = $resource->getLocation();
                return ($location !== '/a/b.php');
            });

        $this->documentEnricher->expects($this->exactly(1))
            ->method('enrichDocument');

        $this->updateQuery->expects($this->exactly(1))
            ->method('addDocuments')
            ->willReturnCallback(function ($documents) {
                $this->assertCount(
                    1,
                    $documents,
                    "one document exprected"
                );
                return $this->updateQuery;
            });

        $parameter = new IndexerParameter(
            'test',
            10,
            10
        );

        $this->indexer->index($parameter);
    }
    public function testAborted(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
                '/a/c.php'
            ]);

        $this->aborter->method('shouldAborted')
            ->willReturn(true);

        $this->aborter->expects($this->once())
            ->method('aborted');

        $this->indexerProgressHandler->expects($this->once())
            ->method('abort');

        $parameter = new IndexerParameter(
            'test',
            10,
            10
        );

        $this->indexer->index($parameter);
    }

    public function testWithUnsuccessfulStatus(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
                '/a/c.php'
            ]);

        $this->updateResult->method('getStatus')
            ->willReturn(500);

        $this->documentEnricher->method('isIndexable')
            ->willReturn(true);

        $this->indexerProgressHandler->expects($this->once())
            ->method('error');

        $parameter = new IndexerParameter(
            'test',
            10,
            10
        );

        $this->indexer->index($parameter);
    }

    public function testWithInvalidResource(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
            ]);

        $this->resourceLoader->method('load')
            ->willThrowException(new InvalidResourceException('/a/b.php'));

        $this->indexerProgressHandler->expects($this->once())
            ->method('error');

        $parameter = new IndexerParameter(
            'test',
            10,
            10
        );

        $this->indexer->index($parameter);
    }

    public function testEmptyStatus(): void
    {
        $this->finder->method('findAll')
            ->willReturn([]);

        $parameter = new IndexerParameter(
            'test',
            10,
            10
        );

        $status = $this->indexer->index($parameter);
        $this->assertEquals(0, $status->total, 'total should be 0');
    }

    public function testIndexPaths(): void
    {
        $this->finder->method('findPaths')
            ->willReturn([
                '/a/b.php',
                '/a/c.php'
            ]);

        $this->updateResult->method('getStatus')
            ->willReturn(0);

        $this->documentEnricher->method('isIndexable')
            ->willReturn(true);

        $this->updateQuery->expects($this->exactly(1))
            ->method('addDocuments')
            ->willReturnCallback(function ($documents) {
                $this->assertCount(
                    2,
                    $documents,
                    "two documents exprected"
                );
                return $this->updateQuery;
            });

        $parameter = new IndexerParameter(
            'test',
            10,
            10,
            [
                '/a/b.php',
                '/a/c.php'
            ]
        );

        $this->indexer->index($parameter);
    }

    public function testIndexPathWithParameter(): void
    {
        $this->finder->expects($this->once())
            ->method('findPaths')
            ->with($this->equalTo(['?a=b']));

        $parameter = new IndexerParameter(
            'test',
            10,
            10,
            [
                '?a=b'
            ]
        );

        $this->indexer->index($parameter);
    }

    public function testIndexPathWithParameterAndPath(): void
    {
        $this->finder->expects($this->once())
            ->method('findPaths')
            ->with($this->equalTo(['/test.php']));

        $parameter = new IndexerParameter(
            'test',
            10,
            10,
            [
                '/test.php?a=b'
            ]
        );

        $this->indexer->index($parameter);
    }

    public function testIndexPathWithLocParameterAndPath(): void
    {
        $this->finder->expects($this->once())
            ->method('findPaths')
            ->with($this->equalTo(['/test.php.translations/en.php']));

        $parameter = new IndexerParameter(
            'test',
            10,
            10,
            [
                '/test.php?loc=en'
            ]
        );

        $this->indexer->index($parameter);
    }

    public function testWithoutAvailableIndexes(): void
    {

        $this->availableIndexes = [];
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
                '/a/c.php',
                '/a/d.php',
                '/a/e.php',
                '/a/f.php',
                '/a/g.php',
                '/a/h.php',
                '/a/i.php',
                '/a/j.php',
                '/a/k.php',
                '/a/l.php'
            ]);

        $parameter = new IndexerParameter(
            'test',
            10,
            10
        );

        $this->indexerProgressHandler->expects($this->once())
            ->method('error');

        $this->indexer->index($parameter);
    }
}
