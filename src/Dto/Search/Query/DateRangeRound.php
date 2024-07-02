<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

/**
 * @codeCoverageIgnore
 */
enum DateRangeRound: string
{
    case START_OF_DAY = 'START_OF_DAY';
    case START_OF_PREVIOUS_DAY = 'START_OF_PREVIOUS_DAY';
    case END_OF_DAY = 'END_OF_DAY';
    case END_OF_PREVIOUS_DAY = 'END_OF_PREVIOUS_DAY';
    case START_OF_MONTH = 'START_OF_MONTH';
    case START_OF_PREVIOUS_MONTH = 'START_OF_PREVIOUS_MONTH';
    case END_OF_MONTH = 'END_OF_MONTH';
    case END_OF_PREVIOUS_MONTH = 'END_OF_PREVIOUS_MONTH';
    case START_OF_YEAR = 'START_OF_YEAR';
    case START_OF_PREVIOUS_YEAR = 'START_OF_PREVIOUS_YEAR';
    case END_OF_YEAR = 'END_OF_YEAR';
    case END_OF_PREVIOUS_YEAR = 'END_OF_PREVIOUS_YEAR';
}
