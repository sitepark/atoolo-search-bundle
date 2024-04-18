<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

use Atoolo\Resource\ResourceLanguage;

interface IndexName
{
    public function name(ResourceLanguage $lang): string;

    /**
     * The returned list contains the default index name and the index
     * name of all language-specific indexes.
     *
     * @return string[]
     */
    public function names(): array;
}
