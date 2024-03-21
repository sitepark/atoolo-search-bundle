<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\Resource;
use Atoolo\Search\Exception\MissMatchingResourceFactoryException;
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
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * @return array<Resource>
     */
    public function loadResourceList(SelectResult $result, string $lang): array
    {
        $resourceList = [];
        /** @var Document $document */
        foreach ($result as $document) {
            try {
                $resourceList[] = $this->loadResource($document, $lang);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), ['exception' => $e]);
            }
        }
        return $resourceList;
    }

    private function loadResource(Document $document, string $lang): Resource
    {

        foreach ($this->resourceFactoryList as $resourceFactory) {
            if ($resourceFactory->accept($document)) {
                return $resourceFactory->create($document, $lang);
            }
        }

        $location = $this->getField($document, 'url') ?? '';

        throw new MissMatchingResourceFactoryException($location);
    }

    private function getField(Document $document, string $name): ?string
    {
        $fields = $document->getFields();
        if (!isset($fields[$name])) {
            return null;
        }
        return $fields[$name];
    }
}
