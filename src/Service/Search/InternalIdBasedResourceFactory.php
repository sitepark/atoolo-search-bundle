<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\Service\IdPathMapper;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Resource\ResourceLocation;
use LogicException;
use Solarium\QueryType\Select\Result\Document;

/**
 * Internal resources are the data that the CMS provides in the form of
 * aggregated PHP files. The Solr document field id specifies which resource
 * is required. The prerequisite for this factory is that the atoolo_resource.id_path_mapper is available.
 */
class InternalIdBasedResourceFactory implements ResourceFactory
{
    public function __construct(
        private readonly ResourceLoader $resourceLoader,
        private readonly ?IdPathMapper $idPathMapper,
    ) {}

    public function accept(Document $document, ResourceLanguage $lang): bool
    {
        if ($this->idPathMapper === null) {
            return false;
        }
        return $this->isInternalResource($document);
    }

    public function create(Document $document, ResourceLanguage $lang): Resource
    {
        $id = $this->getId($document);
        if ($id === null) {
            throw new LogicException('document should contain an id');
        }
        if ($this->idPathMapper === null) {
            throw new LogicException('idPathMapper is required to load internal resources');

        }
        $idParts = explode('-', $id);
        $isEmbeddedMedia = count($idParts) > 1;

        if ($isEmbeddedMedia) {
            $mediaContainerId = (int) $idParts[0];
            $mediaId = (int) $idParts[1];
            $path = '/' . $this->idPathMapper->embeddedMediaPathFor($mediaContainerId, $mediaId) . '.php';
        } else {
            $path = '/' . $this->idPathMapper->pathFor((int) $id) . '.php';
        }

        $location = ResourceLocation::of($path, $lang);
        return $this->resourceLoader->load($location);
    }

    private function getId(Document $document): ?string
    {
        return $document->getFields()['id'] ?? null;
    }

    private function isInternalResource(Document $document): bool
    {
        $sourceList = $document->getFields()['sp_source'] ?? [];
        return in_array('internal', $sourceList);
    }
}
