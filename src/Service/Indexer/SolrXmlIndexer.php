<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Dto\Indexer\SolrXmlIndexerParameter;
use Atoolo\Search\Service\AbstractIndexer;
use Atoolo\Search\Service\IndexName;
use Exception;

/**
 * This indexer can be used to read XML data in Solr format and
 * transfer it to the Solr index.
 * This indexer initially serves as an interim solution to integrate
 * the existing solutions that generate an XML file in this way.
 * In the future, these old solutions will implement their own indexer.
 * Once this has been done, this indexer will no longer be required.
 */
class SolrXmlIndexer extends AbstractIndexer
{
    public function __construct(
        IndexName $indexName,
        IndexerProgressHandler $progressHandler,
        IndexingAborter $aborter,
        private readonly SolrIndexService $indexService,
        IndexerConfigurationLoader $configLoader,
        private readonly SolrXmlReader $xmlReader,
        string $source,
    ) {
        parent::__construct(
            $indexName,
            $progressHandler,
            $aborter,
            $configLoader,
            $source,
        );
    }

    public function index(): IndexerStatus
    {
        $parameter = $this->loadIndexerParameter();

        if (empty($parameter->xmlFileList)) {
            return $this->progressHandler->getStatus();
        }

        $count = 0;
        foreach ($parameter->xmlFileList as $xmlFile) {
            $this->xmlReader->open($xmlFile);
            $count += $this->xmlReader->count();
        }

        $this->progressHandler->start($count);

        $processId = uniqid('', true);
        $successCount = 0;

        foreach ($parameter->xmlFileList as $xmlFile) {
            $this->xmlReader->open($xmlFile);
            while (
                ($docList = $this->xmlReader->next($parameter->chunkSize))
            ) {
                if (
                    $this->isAbortionRequested()
                ) {
                    return $this->progressHandler->getStatus();
                }
                $updater = $this->indexService->updater(ResourceLanguage::default());

                foreach ($docList as $docFields) {
                    /** @var IndexSchema2xDocument $doc */
                    $doc = $updater->createDocument();
                    $doc->crawl_process_id = $processId;
                    $doc->sp_source = [$this->source];
                    foreach ($docFields as $name => $value) {
                        if (empty($value)) {
                            continue;
                        }
                        $doc->addField($name, $value);
                    }
                    $successCount++;
                    $updater->addDocument($doc);
                }
                $result = $updater->update();
                $this->progressHandler->advance(count($docList));

                if ($result->getStatus() !== 0) {
                    $this->progressHandler->error(new Exception(
                        $result->getResponse()->getStatusMessage(),
                    ));
                }
                gc_collect_cycles();
            }
        }

        if (
            $successCount >= $parameter->cleanupThreshold
        ) {
            $this->indexService->deleteExcludingProcessId(
                ResourceLanguage::default(),
                $this->source,
                $processId,
            );
        }

        $this->indexService->commit(ResourceLanguage::default());

        $this->progressHandler->finish();
        gc_collect_cycles();

        return $this->progressHandler->getStatus();

    }

    public function remove(array $idList): void
    {
        $this->indexService->deleteByIdListForAllLanguages(
            $this->source,
            $idList,
        );
    }

    public function getIndex(ResourceLanguage $lang): string
    {
        return $this->indexService->getIndex($lang);
    }

    private function loadIndexerParameter(): SolrXmlIndexerParameter
    {
        $config = $this->configLoader->load($this->source);
        $data = $config->data;

        /** @var array<string> $xmlFileList */
        $xmlFileList = $data->getArray('xmlFileList');

        return new SolrXmlIndexerParameter(
            source: $this->source,
            cleanupThreshold: $data->getInt('cleanupThreshold'),
            chunkSize: $data->getInt('chunkSize'),
            xmlFileList: $xmlFileList,
        );
    }
}
