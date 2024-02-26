<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Service\SolrClientFactory;

class SolrIndexerFactory
{
    /**
     * @param iterable<DocumentEnricher<IndexDocument>> $documentEnricherList
     */
    public function __construct(
        private readonly iterable $documentEnricherList,
        private readonly LocationFinder $finder,
        private readonly ResourceLoader $resourceLoader,
        private readonly TranslationSplitter $translationSplitter,
        private readonly SolrClientFactory $clientFactory,
        private readonly IndexingAborter $aborter,
        private readonly string $source
    ) {
    }

    public function create(
        IndexerProgressHandler $progressHandler
    ): SolrIndexer {
        return new SolrIndexer(
            $this->documentEnricherList,
            $progressHandler,
            $this->finder,
            $this->resourceLoader,
            $this->translationSplitter,
            $this->clientFactory,
            $this->aborter,
            $this->source
        );
    }
}
