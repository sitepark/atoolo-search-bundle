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
 * Nested date documents of events have the URL of the event with a query
 * string (`?date=<timestamp>`). The event is loaded without it and the query
 * string is appended to the url of the resource, so that each hit keeps its
 * date.
 */
class InternalResourceFactory implements ResourceFactory
{
    public function __construct(
        private readonly ResourceLoader $resourceLoader,
    ) {}

    public function accept(Document $document, ResourceLanguage $lang): bool
    {
        $location = $this->getUrl($document);
        if ($location === null) {
            return false;
        }
        return $this->resourceLoader->exists(ResourceLocation::of($location, $lang));
    }

    public function create(Document $document, ResourceLanguage $lang): Resource
    {
        $url = $this->getUrl($document);
        if ($url === null) {
            throw new LogicException('document should contain an url');
        }
        $location = ResourceLocation::of($url, $lang);
        $resource = $this->resourceLoader->load($location);
        $query = $this->getQuery($document);
        if ($query === null) {
            return $resource;
        }
        // Only the query string is taken from the Solr document. The url of
        // the document is the one of the resource at indexing time, the url
        // of the loaded resource is the current one. So a hit of a single
        // date has the same url as the hit of its event, plus the query
        // string, even if the index is not up to date.
        $separator = str_contains($resource->url, '?') ? '&' : '?';
        return new Resource(
            location: $resource->location,
            url: $resource->url . $separator . $query,
            id: $resource->id,
            name: $resource->name,
            objectType: $resource->objectType,
            lang: $resource->lang,
            data: $resource->data,
        );
    }

    private function getUrl(Document $document): ?string
    {
        $url = $this->getRawUrl($document);
        if ($url === null) {
            return null;
        }
        return strstr($url, '?', true) ?: $url;
    }

    private function getQuery(Document $document): ?string
    {
        $query = parse_url($this->getRawUrl($document) ?? '', PHP_URL_QUERY);
        return is_string($query) && $query !== '' ? $query : null;
    }

    private function getRawUrl(Document $document): ?string
    {
        $url = $document->getFields()['url'] ?? null;
        return is_string($url) ? $url : null;
    }
}
