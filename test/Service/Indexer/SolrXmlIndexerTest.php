<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Indexer\IndexerConfiguration;
use Atoolo\Search\Service\Indexer\IndexerConfigurationLoader;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use Atoolo\Search\Service\Indexer\IndexingAborter;
use Atoolo\Search\Service\Indexer\SolrIndexService;
use Atoolo\Search\Service\Indexer\SolrXmlIndexer;
use Atoolo\Search\Service\Indexer\SolrXmlReader;
use Atoolo\Search\Service\IndexName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(SolrXmlIndexer::class)]
class SolrXmlIndexerTest extends TestCase
{
    private SolrXmlIndexer $indexer;

    private SolrIndexService&MockObject $indexService;

    private IndexerConfigurationLoader&Stub $configLoader;

    private IndexerProgressHandler&MockObject $progressHandler;

    private SolrXmlReader&Stub $xmlReader;

    private IndexingAborter&Stub $aborter;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $indexName = $this->createStub(IndexName::class);
        $this->progressHandler = $this->createMock(IndexerProgressHandler::class);
        $this->aborter = $this->createStub(IndexingAborter::class);
        $this->indexService = $this->createMock(SolrIndexService::class);
        $this->configLoader = $this->createStub(IndexerConfigurationLoader::class);
        $this->xmlReader = $this->createStub(SolrXmlReader::class);

        $this->indexer = new SolrXmlIndexer(
            $indexName,
            $this->progressHandler,
            $this->aborter,
            $this->indexService,
            $this->configLoader,
            $this->xmlReader,
            'source',
        );
    }

    public function testRemove(): void
    {
        $this->indexService->expects($this->once())
            ->method('deleteByIdListForAllLanguages');
        $this->indexer->remove(['123']);
    }

    public function testGetIndex(): void
    {
        $lang = $this->createStub(ResourceLanguage::class);
        $this->indexService->expects($this->once())
            ->method('getIndex');
        $this->indexer->getIndex($lang);
    }

    public function testIndexWithEmptyXmlFileList(): void
    {
        $this->configLoader->method('load')
            ->willReturn(new IndexerConfiguration(
                source: 'test',
                name: 'test',
                data: new DataBag([
                    'xmlFileList' => [],
                    'cleanupThreshold' => '1000',
                    'chunkSize' => '500',
                ]),
            ));

        $this->progressHandler->expects($this->never())
            ->method('start');
        $this->progressHandler->expects($this->once())
            ->method('getStatus');

        $this->indexer->index();
    }

    /**
     * @throws Exception
     */
    public function testIndex(): void
    {
        $this->configLoader->method('load')
            ->willReturn(new IndexerConfiguration(
                source: 'test',
                name: 'test',
                data: new DataBag([
                    'xmlFileList' => ['/test.xml'],
                    'cleanupThreshold' => '1000',
                    'chunkSize' => '500',
                ]),
            ));
        $this->xmlReader->method('count')
            ->willReturn(1);

        $this->xmlReader->method('next')
            ->willReturn(
                [
                    [
                        'id' => '1',
                        'name' => 'test',
                        'emptyField' => '',
                    ],
                ],
                [],
            );

        $this->progressHandler->expects($this->once())
            ->method('start')
            ->with(1);
        $this->progressHandler->expects($this->once())
            ->method('advance')
            ->with(1);

        $this->indexer->index();
    }

    /**
     * @throws Exception
     */
    public function testIndexIfAbortionRequested(): void
    {
        $this->configLoader->method('load')
            ->willReturn(new IndexerConfiguration(
                source: 'test',
                name: 'test',
                data: new DataBag([
                    'xmlFileList' => ['/test.xml'],
                    'cleanupThreshold' => '1000',
                    'chunkSize' => '500',
                ]),
            ));
        $this->xmlReader->method('count')
            ->willReturn(1);

        $this->xmlReader->method('next')
            ->willReturn(
                [
                    [
                        'id' => '1',
                        'name' => 'test',
                        'emptyField' => '',
                    ],
                ],
                [],
            );

        $this->aborter->method('isAbortionRequested')
            ->willReturn(true);

        $this->progressHandler->expects($this->once())
            ->method('start')
            ->with(1);
        $this->progressHandler->expects($this->never())
            ->method('advance');

        $this->indexer->index();
    }
}
