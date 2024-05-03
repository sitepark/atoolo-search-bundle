<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use DateInterval;
use DateTime;

class SolrAbsoluteDateRangeFacet extends SolrRangeFacet
{
    /**
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        private readonly DateTime $from,
        private readonly DateTime $to,
        private readonly ?DateInterval $gap,
        array $excludeFilter = []
    ) {
        parent::__construct(
            $key,
            $excludeFilter
        );
    }

    public function getStart(): string
    {
        return SolrDateMapper::mapDateTime($this->from);
    }

    public function getEnd(): string
    {
        return SolrDateMapper::mapDateTime($this->to);
    }

    public function getGap(): ?string
    {
        if ($this->gap === null) {
            return null;
        }
        return SolrDateMapper::mapDateInterval($this->gap, '+');
    }
}
