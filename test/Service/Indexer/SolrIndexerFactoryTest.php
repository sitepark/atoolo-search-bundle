<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Service\Indexer\IndexerProgressHandler;
use Atoolo\Search\Service\Indexer\IndexingAborter;
use Atoolo\Search\Service\Indexer\LocationFinder;
use Atoolo\Search\Service\Indexer\SolrIndexerFactory;
use Atoolo\Search\Service\Indexer\TranslationSplitter;
use Atoolo\Search\Service\SolrClientFactory;
use PHPUnit\Framework\TestCase;

class SolrIndexerFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new SolrIndexerFactory(
            [],
            $this->createStub(LocationFinder::class),
            $this->createStub(ResourceLoader::class),
            $this->createStub(TranslationSplitter::class),
            $this->createStub(SolrClientFactory::class),
            $this->createStub(IndexingAborter::class),
            ''
        );

        $this->expectNotToPerformAssertions();
        $factory->create(
            $this->createStub(IndexerProgressHandler::class)
        );
    }
}
