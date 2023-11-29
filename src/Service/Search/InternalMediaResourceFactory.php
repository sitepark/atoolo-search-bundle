<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Service\Search\ResourceFactory;
use Solarium\QueryType\Select\Result\Document;

/**
 * The metadata is indexed for media and the full text is also indexed for
 * text documents. To load the resource of a medium, the meta file of the
 * medium is required. The medium meta file is identical to the path of the
 * medium plus the suffix .meta.php. If this file exists for the URL of the
 * Solr document, the media resource is created.
 */
class InternalMediaResourceFactory implements ResourceFactory
{
    public function __construct(
        private readonly ResourceLoader $resourceLoader
    ) {
    }

    public function accept(Document $document): bool
    {
        $metaLocation = $this->getMetaLocation($document);
        return $this->resourceLoader->exists($metaLocation);
    }

    public function create(Document $document): Resource
    {
        $metaLocation = $this->getMetaLocation($document);
        return $this->resourceLoader->load($metaLocation);
    }

    private function getMetaLocation(Document $document): string
    {
        return $document->url . '.meta.php';
    }
}
