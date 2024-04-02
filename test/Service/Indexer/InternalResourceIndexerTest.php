<?php

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Dto\Indexer\IndexerConfiguration;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexerConfigurationLoader;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use Atoolo\Search\Service\Indexer\IndexingAborter;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use Atoolo\Search\Service\Indexer\InternalResourceIndexer;
use Atoolo\Search\Service\Indexer\LocationFinder;
use Atoolo\Search\Service\Indexer\ResourceFilter;
use Atoolo\Search\Service\Indexer\SiteKit\SubDirTranslationSplitter;
use Atoolo\Search\Service\Indexer\SolrIndexService;
use Atoolo\Search\Service\Indexer\SolrIndexUpdater;
use Atoolo\Search\Service\Indexer\TranslationSplitter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Solarium\QueryType\Update\Result as UpdateResult;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\SemaphoreStore;

#[CoversClass(InternalResourceIndexer::class)]
class InternalResourceIndexerTest extends TestCase
{
    /**
    * @var string[]
     */
    private array $availableIndexes = ['test', 'test-en_US'];

    public ResourceFilter&MockObject $indexerFilter;

    private ResourceLoader&Stub $resourceLoader;

    private IndexerProgressHandler&MockObject $indexerProgressHandler;

    private InternalResourceIndexer $indexer;

    private SolrIndexService&MockObject $solrIndexService;

    private LocationFinder&MockObject $finder;

    private SolrIndexUpdater&MockObject $updater;

    private UpdateResult&Stub $updateResult;

    private IndexingAborter&MockObject $aborter;

    private DocumentEnricher&MockObject $documentEnricher;

    private TranslationSplitter $translationSplitter;

    private IndexerConfigurationLoader&MockObject $indexerConfigurationLoader;

    private IndexerConfiguration $indexerConfiguration;

    private LockFactory $lockFactory;

    private LoggerInterface&MockObject $logger;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->indexerFilter = $this->createMock(
            ResourceFilter::class
        );

        $this->indexerProgressHandler = $this->createMock(
            IndexerProgressHandler::class
        );
        $this->finder = $this->createMock(LocationFinder::class);
        $this->documentEnricher = $this->createMock(DocumentEnricher::class);
        $this->documentEnricher
            ->method('enrichDocument')
            ->willReturnCallback(function ($resource, $doc) {
                return $doc;
            });
        $this->translationSplitter = new SubDirTranslationSplitter();
        $this->resourceLoader = $this->createStub(ResourceLoader::class);
        $this->resourceLoader->method('load')
            ->willReturnCallback(function ($path) {
                $resource = $this->createStub(Resource::class);
                $resource->method('getLocation')
                    ->willReturn($path);
                return $resource;
            });
        $this->solrIndexService = $this->createMock(SolrIndexService::class);
        $this->updateResult = $this->createStub(UpdateResult::class);
        $this->updater = $this->createMock(SolrIndexUpdater::class);
        $this->updater->method('update')->willReturn($this->updateResult);
        $this->updater->method('createDocument')->willReturn(
            new IndexSchema2xDocument()
        );
        $this->solrIndexService->method('getManagedIndices')
            ->willReturnCallback(function () {
                return $this->availableIndexes;
            });
        $this->solrIndexService->method('getIndex')
            ->willReturnCallback(function ($lang) {
                if ($lang === 'en') {
                    return 'test-en_US';
                }
                return 'test';
            });
        $this->solrIndexService->method('updater')
            ->willReturn($this->updater);
        $this->aborter =  $this->createMock(IndexingAborter::class);
        $this->indexerConfiguration = new IndexerConfiguration(
            'test-source',
            'Indexer-Name',
            new DataBag([
                'cleanupThreshold' =>  10,
                'chunkSize' => 10
            ])
        );
        $this->indexerConfigurationLoader = $this->createMock(
            IndexerConfigurationLoader::class
        );
        $this->indexerConfigurationLoader->method('load')
            ->willReturn($this->indexerConfiguration);

        $this->lockFactory = new LockFactory(new SemaphoreStore());
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->indexer = new InternalResourceIndexer(
            [ $this->documentEnricher ],
            $this->indexerFilter,
            $this->indexerProgressHandler,
            $this->finder,
            $this->resourceLoader,
            $this->translationSplitter,
            $this->solrIndexService,
            $this->aborter,
            $this->indexerConfigurationLoader,
            'test-source',
            $this->lockFactory,
            $this->logger
        );
    }

    public function testAbort(): void
    {
        $this->aborter->expects($this->once())
            ->method('requestAbortion')
            ->with('test');

        $this->indexer->abort();
    }

    public function testRemove(): void
    {
        $this->solrIndexService->expects($this->once())
            ->method('deleteByIdListForAllLanguages');

        $this->indexer->remove(['123']);
    }

    public function testRemoveEmpty(): void
    {
        $this->solrIndexService->expects($this->exactly(0))
            ->method('deleteByIdListForAllLanguages');

        $this->indexer->remove([]);
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
        $this->indexerFilter->method('accept')
            ->willReturn(true);

        $this->documentEnricher
            ->method('enrichDocument')
            ->willReturnCallback(function ($resource, $doc) {
                if ($resource->getLocation() === '/a/error.php') {
                    throw new \Exception('test');
                }
                return $doc;
            });

        $this->updater->expects($this->exactly(12))
            ->method('addDocument');

        $this->updater->expects($this->exactly(3))
            ->method('update');

        $this->indexerProgressHandler->expects($this->exactly(2))
            ->method('error');

        $this->indexer->index();
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

        $this->indexerFilter->method('accept')
            ->willReturnCallback(function (Resource $resource) {
                $location = $resource->getLocation();
                return ($location !== '/a/b.php');
            });

        $this->updater->expects($this->exactly(1))
            ->method('addDocument');

        $this->updater->expects($this->exactly(1))
            ->method('update');

        $this->indexer->index();
    }

    public function testAborted(): void
    {
        $this->finder->method('findAll')
            ->willReturn([
                '/a/b.php',
                '/a/c.php'
            ]);

        $this->aborter->method('isAbortionRequested')
            ->willReturn(true);

        $this->aborter->expects($this->once())
            ->method('resetAbortionRequest');

        $this->indexerProgressHandler->expects($this->once())
            ->method('abort');

        $this->indexer->index();
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

        $this->indexerProgressHandler->expects($this->once())
            ->method('error');

        $this->indexer->index();
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

        $this->indexer->index();
    }

    public function testUpdate(): void
    {
        $this->finder->method('findPaths')
            ->willReturn([
                '/a/b.php',
                '/a/c.php'
            ]);

        $this->updateResult->method('getStatus')
            ->willReturn(0);

        $this->indexerFilter->method('accept')
            ->willReturn(true);

        $this->updater->expects($this->exactly(2))
            ->method('addDocument');

        $this->updater->expects($this->exactly(1))
            ->method('update');

        $this->indexer->update([
            '/a/b.php',
            '/a/c.php'
        ]);
    }

    public function testUpdateOtherLang(): void
    {
        $this->finder->method('findPaths')
            ->willReturn([
                '/a/b.php.translations/en_US.php',
            ]);

        $this->updateResult->method('getStatus')
            ->willReturn(0);

        $this->indexerFilter->method('accept')
            ->willReturn(true);

        $this->updater->expects($this->exactly(1))
            ->method('addDocument');

        $this->updater->expects($this->exactly(1))
            ->method('update');

        $this->indexer->update([
            '/a/b.php.translations/en_US.php',
        ]);
    }

    public function testUpdateWithParameter(): void
    {
        $this->finder->expects($this->once())
            ->method('findPaths')
            ->with($this->equalTo(['?a=b']));

        $this->indexer->update([
            '?a=b'
        ]);
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

        $this->indexerProgressHandler->expects($this->once())
            ->method('error');

        $this->indexer->index();
    }

    public function testEnabled(): void
    {
        $this->assertTrue(
            $this->indexer->enabled(),
            'indexer should be always enabled'
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals(
            'Indexer-Name',
            $this->indexer->getName(),
            'unexpected Indexer Name'
        );
    }

    public function testGetProgressHandler(): void
    {
        $this->assertEquals(
            $this->indexerProgressHandler,
            $this->indexer->getProgressHandler(),
            'unexpected progress handler'
        );
    }

    public function testSetProgressHandler(): void
    {
        $progressHandler = $this->createStub(IndexerProgressHandler::class);
        $this->indexer->setProgressHandler($progressHandler);
        $this->assertEquals(
            $progressHandler,
            $this->indexer->getProgressHandler(),
            'unexpected progress handler'
        );
    }

    public function testGetSource(): void
    {
        $this->assertEquals(
            'test-source',
            $this->indexer->getSource(),
            'unexpected source'
        );
    }

    public function testLock(): void
    {
        $this->logger->expects($this->once())
            ->method('notice')
            ->with('Indexer with source "test-source" is already running');
        $lock = $this->lockFactory->createLock('indexer.test-source');
        try {
            $lock->acquire();
            $this->indexer->index();
        } finally {
            $lock->release();
        }
    }
}
