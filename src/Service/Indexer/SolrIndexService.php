<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Service\IndexName;
use Atoolo\Search\Service\SolrClientFactory;
use Solarium\Client;

class SolrIndexService
{
    public function __construct(
        private readonly IndexName $index,
        private readonly SolrClientFactory $clientFactory,
    ) {}

    public function getIndex(ResourceLanguage $lang): string
    {
        return $this->index->name($lang);
    }

    public function updater(ResourceLanguage $lang): SolrIndexUpdater
    {
        $client = $this->createClient($lang);
        $update = $client->createUpdate();
        $update->setDocumentClass(IndexSchema2xDocument::class);

        return new SolrIndexUpdater($client, $update);
    }

    public function deleteExcludingProcessId(
        ResourceLanguage $lang,
        string $source,
        string $processId,
    ): void {
        $this->deleteByQuery(
            $lang,
            '-crawl_process_id:' . $processId . ' AND ' .
            ' sp_source:' . $source,
        );
    }

    /**
     * @param string[] $idList
     */
    public function deleteByIdListForAllLanguages(
        string $source,
        array $idList,
    ): void {
        $this->deleteByQueryForAllLanguages(
            'sp_id:(' . implode(' ', $idList) . ') AND ' .
            'sp_source:' . $source,
        );
    }

    public function deleteByQueryForAllLanguages(string $query): void
    {
        foreach ($this->getManagedIndices() as $index) {
            $client = $this->clientFactory->create($index);
            $update = $client->createUpdate();
            $update->addDeleteQuery($query);
            $client->update($update);
        }
    }

    public function deleteByQuery(ResourceLanguage $lang, string $query): void
    {
        $client = $this->createClient($lang);
        $update = $client->createUpdate();
        $update->addDeleteQuery($query);
        $client->update($update);
    }

    public function commit(ResourceLanguage $lang): void
    {
        $client = $this->createClient($lang);
        $update = $client->createUpdate();
        $update->addCommit();
        $update->addOptimize();
        $client->update($update);
    }

    public function commitForAllLanguages(): void
    {
        foreach ($this->getManagedIndices() as $index) {
            $client = $this->clientFactory->create($index);
            $update = $client->createUpdate();
            $update->addCommit();
            $update->addOptimize();
            $client->update($update);
        }
    }

    /**
     * @return string[]
     */
    public function getManagedIndices(): array
    {
        $client = $this->createClient(ResourceLanguage::default());
        $coreAdminQuery = $client->createCoreAdmin();
        $statusAction = $coreAdminQuery->createStatus();
        $coreAdminQuery->setAction($statusAction);

        $requiredIndexes = $this->index->names();

        $managedIndexes = [];
        $response = $client->coreAdmin($coreAdminQuery);
        $statusResults = $response->getStatusResults() ?? [];
        foreach ($statusResults as $statusResult) {
            $index = $statusResult->getCoreName();
            if (in_array($index, $requiredIndexes, true)) {
                $managedIndexes[] = $index;
            }
        }

        return $managedIndexes;
    }

    private function createClient(ResourceLanguage $lang): Client
    {
        return $this->clientFactory->create($this->index->name($lang));
    }
}
