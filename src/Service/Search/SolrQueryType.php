<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

enum SolrQueryType: string
{
    case QUERY_TYPE_DEFAULT = 'default';
    case QUERY_TYPE_PARENT = 'parentfq';
    case QUERY_TYPE_CHILD = 'childfq';
}
