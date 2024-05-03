<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Exception\UnsupportedIndexLanguageException;

interface IndexName
{
    /**
     * @throws UnsupportedIndexLanguageException Is thrown if no valid index
     *  can be determined for the language.
     */
    public function name(ResourceLanguage $lang): string;

    /**
     * The returned list contains the default index name and the index
     * name of all language-specific indexes.
     *
     * @return string[]
     */
    public function names(): array;
}
