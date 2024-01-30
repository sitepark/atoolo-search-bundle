<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

interface TranslationSplitter
{
    /**
     * @param string[] $pathList
     */
    public function split(array $pathList): TranslationSplitterResult;
}
