<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLocation;

class TranslationSplitterResult
{
    /**
     * @param ResourceLocation[] $bases
     * @param array<string,array<ResourceLocation>> $translations
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
     * @return ResourceLocation[]
     */
    public function getBases(): array
    {
        return $this->bases;
    }

     /**
     * @return ResourceLocation[]
     */
    public function getTranslations(ResourceLanguage $lang): array
    {
        return $this->translations[$lang->code] ?? [];
    }
}
