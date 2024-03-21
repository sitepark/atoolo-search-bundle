<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceChannelFactory;

class ResourceChannelBasedIndexName implements IndexName
{
    public function __construct(
        private readonly ResourceChannelFactory $resourceChannelFactory
    ) {
    }

    public function name(string $lang): string
    {
        $resourceChannel = $this->resourceChannelFactory->create();

        $locale = $this->langToAvailableLocale($resourceChannel, $lang);
        if (empty($locale)) {
            return $resourceChannel->searchIndex;
        }
        return $resourceChannel->searchIndex . '-' . $locale;
    }

    /**
     * The returned list contains the default index name and the index
     * name of all language-specific indexes.
     *
     * @return string[]
     */
    public function names(): array
    {
        $resourceChannel = $this->resourceChannelFactory->create();
        $names = [$resourceChannel->searchIndex];
        foreach (
            $resourceChannel->translationLocales as $locale
        ) {
            $names[] = $resourceChannel->searchIndex . '-' . $locale;
        }
        return $names;
    }

    private function langToAvailableLocale(
        ResourceChannel $resourceChannel,
        string $lang
    ): string {

        if (empty($lang)) {
            return $lang;
        }

        foreach (
            $resourceChannel->translationLocales as $availableLocale
        ) {
            if (str_starts_with($availableLocale, $lang)) {
                return $availableLocale;
            }
        }
        return '';
    }
}
