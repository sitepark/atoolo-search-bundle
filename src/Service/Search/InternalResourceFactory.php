<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Service\Search\ResourceFactory;
use Solarium\QueryType\Select\Result\Document;

/**
 * Internal resources are the data that the CMS provides in the form of
 * aggregated PHP files. The Solr document field url specifies which resource
 * is required. If the URL ends with .php, this factory is used.
 */
class InternalResourceFactory implements ResourceFactory
{
    public function __construct(
        private readonly ResourceLoader $resourceLoader
    ) {
    }

    public function accept(Document $document): bool
    {
        return str_ends_with($document->url, '.php');
    }

    public function create(Document $document): Resource
    {
        return $this->resourceLoader->load($document->url);
    }
}
