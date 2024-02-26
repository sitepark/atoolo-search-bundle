<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Resource\Loader\SiteKitLoader;
use Atoolo\Resource\Loader\StaticResourceBaseLocator;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexDocumentDumper;

class IndexDocumentDumperBuilder
{
    private string $resourceDir;
    /**
     * phpcs:ignore
     * @var iterable<DocumentEnricher<IndexDocument>>
     */
    private iterable $documentEnricherList;

    public function resourceDir(string $resourceDir): IndexDocumentDumperBuilder
    {
        $this->resourceDir = $resourceDir;
        return $this;
    }

    /**
     * phpcs:ignore
     * @param iterable<DocumentEnricher<IndexDocument>> $documentEnricherList
     */
    public function documentEnricherList(
        iterable $documentEnricherList
    ): IndexDocumentDumperBuilder {
        $this->documentEnricherList = $documentEnricherList;
        return $this;
    }

    public function build(): IndexDocumentDumper
    {
        $resourceBaseLocator = new StaticResourceBaseLocator(
            $this->resourceDir
        );
        $resourceLoader = new SiteKitLoader($resourceBaseLocator);

        return new IndexDocumentDumper(
            $resourceLoader,
            $this->documentEnricherList
        );
    }
}
