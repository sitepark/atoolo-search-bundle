<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;
use LogicException;
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

    public function accept(Document $document, ResourceLanguage $lang): bool
    {
        $location = $this->getUrl($document);
        if ($location === null) {
            return false;
        }
        return str_ends_with($location, '.php');
    }

    public function create(Document $document, ResourceLanguage $lang): Resource
    {
        $url = $this->getUrl($document);
        if ($url === null) {
            throw new LogicException('document should contain an url');
        }
        $location = ResourceLocation::of($url, $lang);
        return $this->resourceLoader->load($location);
    }

    private function getUrl(Document $document): ?string
    {
        return $document->getFields()['url'] ?? null;
    }
}
