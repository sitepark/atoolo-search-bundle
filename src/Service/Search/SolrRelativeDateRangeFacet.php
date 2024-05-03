<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Search\Dto\Search\Query\DateRangeRound;
use DateInterval;

class SolrRelativeDateRangeFacet extends SolrRangeFacet
{
    /**
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        private readonly ?\DateTime $base,
        private readonly ?DateInterval $before,
        private readonly ?DateInterval $after,
        private readonly ?DateInterval $gap,
        private readonly ?DateRangeRound $roundStart,
        private readonly ?DateRangeRound $roundEnd,
        array $excludeFilter = []
    ) {
        parent::__construct(
            $key,
            $excludeFilter
        );
    }

    public function getStart(): string
    {
        if ($this->before === null) {
            return $this->roundStart($this->getBaseInSolrSyntax());
        }

        return $this->roundStart(
            $this->getBaseInSolrSyntax() .
            SolrDateMapper::mapDateInterval($this->before, '-')
        );
    }

    public function getEnd(): string
    {
        if ($this->after === null) {
            return $this->roundEnd($this->getBaseInSolrSyntax());
        }
        return $this->roundEnd(
            $this->getBaseInSolrSyntax() .
            SolrDateMapper::mapDateInterval($this->after, '+')
        );
    }

    private function roundStart(string $start): string
    {
        return $start . SolrDateMapper::mapDateRangeRound(
            $this->roundStart ?? DateRangeRound::START_OF_DAY
        );
    }

    private function roundEnd(string $start): string
    {
        return $start . SolrDateMapper::mapDateRangeRound(
            $this->roundEnd ?? DateRangeRound::END_OF_DAY
        );
    }

    public function getGap(): ?string
    {
        if ($this->gap === null) {
            return null;
        }
        return SolrDateMapper::mapDateInterval($this->gap, '+');
    }

    private function getBaseInSolrSyntax(): string
    {
        if ($this->base === null) {
            return 'NOW';
        }

        return SolrDateMapper::mapDateTime($this->base);
    }
}
