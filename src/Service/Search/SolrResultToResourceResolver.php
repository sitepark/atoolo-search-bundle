<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Search\Exception\MissMatchingResourceFactoryException;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Solarium\QueryType\Select\Result\Document;
use Solarium\QueryType\Select\Result\Result as SelectResult;

/**
 * The loadResourceList() method receives a Solr result and generates the
 * corresponding resource objects from each hit with the help of the resource
 * factories and returns them as a list.
 */
class SolrResultToResourceResolver
{
    /**
     * @param iterable<ResourceFactory> $resourceFactoryList
     */
    public function __construct(
        private readonly iterable $resourceFactoryList,
        private readonly SolrExplainBuilder $explainBuilder,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @return array<Resource>
     */
    public function loadResourceList(
        SelectResult $result,
        ResourceLanguage $lang,
    ): array {
        $resourceList = [];
        /** @var Document $document */
        foreach ($result as $document) {
            try {
                $resourceList[] = $this->createResource($document, $lang);
            } catch (Exception $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }
        }
        return $resourceList;
    }

    private function createResource(
        Document $document,
        ResourceLanguage $lang,
    ): Resource {
        $resource = $this->loadResource($document, $lang);
        $explain = $document->getFields()['explain'] ?? [];
        if (!empty($explain)) {
            $explain = $this->explainBuilder->build($explain);

            $rawData = $resource->data->get();
            $rawData['explain'] = $explain;

            return new Resource(
                $resource->location,
                $resource->id,
                $resource->name,
                $resource->objectType,
                $resource->lang,
                new DataBag($rawData),
            );
        }
        return $resource;
    }

    private function loadResource(
        Document $document,
        ResourceLanguage $lang,
    ): Resource {

        foreach ($this->resourceFactoryList as $resourceFactory) {
            if ($resourceFactory->accept($document, $lang)) {
                return $resourceFactory->create($document, $lang);
            }
        }

        throw new MissMatchingResourceFactoryException(
            ResourceLocation::of(
                $document->getFields()['url'] ?? '',
                $lang,
            ),
        );
    }
}
