<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Index\Service\Indexer\IndexUpdateResult;
use Solarium\QueryType\Update\Result as SolariumUpdateResult;

/**
 * Solarium's update result, extended by the backend agnostic
 * {@see IndexUpdateResult} port.
 *
 * It is set via `UpdateQuery::setResultClass()`, so existing callers of
 * `SolrIndexUpdater::update()` still receive a Solarium result.
 */
class SolrUpdateResult extends SolariumUpdateResult implements IndexUpdateResult
{
    public function isSuccess(): bool
    {
        return $this->getStatus() === 0;
    }

    public function getErrorMessage(): ?string
    {
        return $this->getResponse()->getStatusMessage();
    }
}
