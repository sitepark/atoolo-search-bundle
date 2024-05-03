<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

class IndexingAborter
{
    public function __construct(
        private readonly string $workdir,
        private readonly string $type
    ) {
    }

    public function isAbortionRequested(string $index): bool
    {
        return file_exists($this->getAbortMarkerFile($index));
    }

    public function requestAbortion(string $index): void
    {
        touch($this->getAbortMarkerFile($index));
    }

    public function resetAbortionRequest(string $index): void
    {
        unlink($this->getAbortMarkerFile($index));
    }

    private function getAbortMarkerFile(string $index): string
    {
        return $this->workdir . '/' . $this->type . '-' . $index . '.abort';
    }
}
