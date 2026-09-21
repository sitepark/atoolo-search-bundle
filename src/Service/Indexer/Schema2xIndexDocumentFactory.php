<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Index\Service\Indexer\IndexDocumentFactory;

/**
 * Creates the Solr schema 2.x document. It is used by the
 * {@see IndexDocumentDumper} of the source `internal`, so that a dump shows
 * exactly the document an index run writes.
 */
class Schema2xIndexDocumentFactory implements IndexDocumentFactory
{
    public function create(): IndexSchema2xDocument
    {
        return new IndexSchema2xDocument();
    }
}
