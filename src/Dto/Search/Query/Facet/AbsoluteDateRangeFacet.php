<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Facet;

use DateInterval;
use DateTime;

/**
 * @codeCoverageIgnore
 */
class AbsoluteDateRangeFacet extends Facet
{
    /**
     * @param string[] $excludeFilter
     */
    public function __construct(
        string $key,
        public readonly ?DateTime $from,
        public readonly ?DateTime $to,
        public readonly ?DateInterval $gap,
        array $excludeFilter = [],
    ) {
        parent::__construct(
            $key,
            $excludeFilter,
        );
    }
}
