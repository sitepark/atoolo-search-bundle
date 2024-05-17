<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use LogicException;
use Solarium\QueryType\Select\Result\Document;

/**
 * External resources are data that is not provided by the CMS, but are
 * transferred to the Solr index via special indexers, for example.
 * In these cases, the search result leads to an external page.
 * This factory recognizes external content using the Solr document field url.
 * It is an external resource if the URL begins with https:// or http://.
 */
class ExternalResourceFactory implements ResourceFactory
{
    public function accept(Document $document, ResourceLanguage $lang): bool
    {
        $location = $this->getField($document, 'url');
        if ($location === '') {
            return false;
        }
        return (
            str_starts_with($location, 'http://') ||
            str_starts_with($location, 'https://')
        );
    }

    public function create(Document $document, ResourceLanguage $lang): Resource
    {
        $location = $this->getField($document, 'url');
        if ($location === '') {
            throw new LogicException('document should contain an url');
        }

        return new Resource(
            location: $location,
            id: $this->getField($document, 'sp_id'),
            name: $this->getField($document, 'title'),
            objectType: $this->getField(
                $document,
                'sp_objecttype',
                'external'
            ),
            lang: ResourceLanguage::of(
                $this->getField($document, 'meta_content_language')
            ),
            data: new DataBag([
                'base' => [
                    'teaser' => [
                        'text' => $this->getField($document, 'description')
                    ]
                ]
            ]),
        );
    }

    private function getField(
        Document $document,
        string $name,
        string $default = ''
    ): string {
        $value = $document->getFields()[$name] ?? null;
        if ($value === null) {
            return $default;
        }

        if (is_array($value)) {
            return implode(' ', $value);
        }

        return (string)$value;
    }
}
