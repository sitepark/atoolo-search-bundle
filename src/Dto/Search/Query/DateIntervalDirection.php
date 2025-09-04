<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

/**
 * @codeCoverageIgnore
 */
enum DateIntervalDirection: string
{
    case PAST = 'PAST';
    case FUTURE = 'FUTURE';
}
