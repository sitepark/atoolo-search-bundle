<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service;

use Atoolo\Search\Dto\Indexer\IndexerStatus;
use Atoolo\Search\Service\AbstractIndexer;

class TextIndexer extends AbstractIndexer
{
    /**
     * @inheritDoc
     */
    public function index(): IndexerStatus
    {
        return $this->progressHandler->getStatus();
    }

    /**
     * @inheritDoc
     */
    public function remove(array $idList): void {}

    /**
     * make this method public for testing
     */
    public function isAbortionRequested(): bool
    {
        return parent::isAbortionRequested();
    }
}
