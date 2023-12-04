<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLoader;
use Atoolo\Search\Exception\MissMatchingResourceFactoryException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Solarium\Core\Query\Result\ResultInterface;
use Solarium\QueryType\Select\Result\Document;

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
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * @return array<Resource>
     */
    public function loadResourceList(ResultInterface $result): array
    {
        $resourceList = [];
        foreach ($result as $document) {
            try {
                $resourceList[] = $this->loadResource($document);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }
        }
        return $resourceList;
    }

    private function loadResource(Document $document): Resource
    {

        foreach ($this->resourceFactoryList as $resourceFactory) {
            if ($resourceFactory->accept($document)) {
                return $resourceFactory->create($document);
            }
        }

        throw new MissMatchingResourceFactoryException($document->url);
    }
}