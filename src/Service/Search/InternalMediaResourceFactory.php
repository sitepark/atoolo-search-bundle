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

    public function accept(Document $document, ResourceLanguage $lang): bool
    {
        $metaLocation = $this->getMetaLocation($document, $lang);
        if ($metaLocation === null) {
            return false;
        }
        return $this->resourceLoader->exists($metaLocation);
    }

    public function create(Document $document, ResourceLanguage $lang): Resource
    {
        $metaLocation = $this->getMetaLocation($document, $lang);
        if ($metaLocation === null) {
            throw new LogicException('document should contain an url');
        }
        return $this->resourceLoader->load($metaLocation);
    }

    private function getMetaLocation(
        Document $document,
        ResourceLanguage $lang
    ): ?ResourceLocation {
        $url = $this->getField($document, 'url');
        if ($url === null) {
            return null;
        }
        return ResourceLocation::of(
            $url . '.meta.php',
            $lang
        );
    }

    private function getField(Document $document, string $name): ?string
    {
        return $document->getFields()[$name] ?? null;
    }
}
