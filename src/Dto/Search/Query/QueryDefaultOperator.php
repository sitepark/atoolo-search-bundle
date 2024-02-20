<?php

namespace Atoolo\Search\Dto\Search\Query;

enum QueryDefaultOperator: string
{
    case AND = 'AND';
    case OR = 'OR';
}
