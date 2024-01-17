<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

interface TranslationSplitter
{
    public function split(array $pathList): TranslationSplitterResult;
}
