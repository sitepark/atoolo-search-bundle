<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\Resource;
use Solarium\Core\Query\DocumentInterface;

/**
 * This interface can be used to implement enricher with the help of which a
 * Solr document can be enriched on the basis of a resource.
 */
interface DocumentEnricher
{
    public function isIndexable(Resource $resource): bool;

    public function enrichDocument(
        Resource $resource,
        DocumentInterface $doc,
        string $processId
    ): DocumentInterface;
}
