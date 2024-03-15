<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Search\Service\SolrClientFactory;
use LogicException;

class SolrIndexService
{
    public function __construct(
        private readonly SolrClientFactory $clientFactory
    ) {
    }

    public function getIndex(?string $locale = null): string
    {
        $client = $this->clientFactory->create($locale);
        $core = $client->getEndpoint()->getCore();
        if ($core === null) {
            throw new LogicException('Core is not set in Solr client');
        }
        return $core;
    }

    public function updater(?string $locale = null): SolrIndexUpdater
    {
        $client = $this->clientFactory->create($locale);
        $update = $client->createUpdate();
        $update->setDocumentClass(IndexSchema2xDocument::class);

        return new SolrIndexUpdater($client, $update);
    }

    public function deleteExcludingProcessId(
        ?string $locale,
        string $source,
        string $processId
    ): void {
        $this->deleteByQuery(
            $locale,
            '-crawl_process_id:' . $processId . ' AND ' .
            ' sp_source:' . $source
        );
    }

    /**
     * @param string[] $idList
     */
    public function deleteByIdList(
        ?string $locale,
        string $source,
        array $idList
    ): void {
        $this->deleteByQuery(
            $locale,
            'sp_id:(' . implode(' ', $idList) . ') AND ' .
            'sp_source:' . $source
        );
    }

    public function deleteByQuery(?string $locale, string $query): void
    {
        $client = $this->clientFactory->create($locale);
        $update = $client->createUpdate();
        $update->addDeleteQuery($query);
        $client->update($update);
    }

    public function commit(?string $locale): void
    {
        $client = $this->clientFactory->create($locale);
        $update = $client->createUpdate();
        $update->addCommit();
        $update->addOptimize();
        $client->update($update);
    }

    /**
     * @return string[]
     */
    public function getAvailableIndexes(): array
    {
        $client = $this->clientFactory->create('');
        $coreAdminQuery = $client->createCoreAdmin();
        $statusAction = $coreAdminQuery->createStatus();
        $coreAdminQuery->setAction($statusAction);

        $availableIndexes = [];
        $response = $client->coreAdmin($coreAdminQuery);
        $statusResults = $response->getStatusResults() ?? [];
        foreach ($statusResults as $statusResult) {
            $availableIndexes[] = $statusResult->getCoreName();
        }

        return $availableIndexes;
    }
}
