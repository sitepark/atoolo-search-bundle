<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\ResourceLanguage;

class TranslationSplitterResult
{
    /**
     * @param string[] $bases
     * @param array<string,array<string>> $translations
     */
    public function __construct(
        private readonly array $bases,
        private readonly array $translations
    ) {
    }

    /**
     * @return ResourceLanguage[]
     */
    public function getLanguages(): array
    {
        $languages = array_keys($this->translations);
        sort($languages);
        return array_map(
            static fn($lang)
                => ResourceLanguage::of($lang),
            $languages
        );
    }

    /**
     * @return string[]
     */
    public function getBases(): array
    {
        return $this->bases;
    }

     /**
     * @return string[]
     */
    public function getTranslations(ResourceLanguage $lang): array
    {
        return $this->translations[$lang->code] ?? [];
    }
}
