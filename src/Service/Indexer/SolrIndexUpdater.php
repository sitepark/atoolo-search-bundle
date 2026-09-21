<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Index\Service\Indexer\IndexDocument;
use Atoolo\Index\Service\Indexer\IndexUpdater;
use InvalidArgumentException;
use Solarium\Client;
use Solarium\QueryType\Update\Query\Document;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;

class SolrIndexUpdater implements IndexUpdater
{
    /**
     * @var Document[]
     */
    private array $documents = [];

    public function __construct(
        private readonly Client $client,
        private readonly UpdateQuery $update,
    ) {}

    public function createDocument(): IndexSchema2xDocument
    {
        /** @var IndexSchema2xDocument $doc */
        $doc = $this->update->createDocument();
        return $doc;
    }

    /**
     * The port declares {@see IndexDocument}. Parameters are contravariant,
     * so the type cannot be narrowed to the Solr document in the signature;
     * it is checked here instead.
     */
    public function addDocument(IndexDocument $document): void
    {
        if (!$document instanceof Document) {
            throw new InvalidArgumentException(
                'Solr can only index a '
                . Document::class . ', got ' . $document::class,
            );
        }
        $this->documents[] = $document;
    }

    public function clearDocuments(): void
    {
        foreach ($this->update->getCommands() as $command) {
            $this->update->remove($command);
        };
    }

    public function update(): SolrUpdateResult
    {
        $this->update->addDocuments($this->documents);
        $this->documents = [];
        /** @var SolrUpdateResult $result */
        $result = $this->client->update($this->update);
        return $result;
    }
}
