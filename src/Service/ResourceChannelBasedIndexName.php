<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Exception\UnsupportedIndexLanguageException;

class ResourceChannelBasedIndexName implements IndexName
{
    public function __construct(
        private readonly ResourceChannel $resourceChannel,
    ) {}

    /**
     * @throws UnsupportedIndexLanguageException
     */
    public function name(ResourceLanguage $lang): string
    {
        $locale = $this->langToAvailableLocale($this->resourceChannel, $lang);

        if (empty($locale)) {
            return $this->resourceChannel->searchIndex;
        }

        return $this->resourceChannel->searchIndex . '-' . $locale;
    }

    /**
     * The returned list contains the default index name and the index
     * name of all language-specific indexes.
     *
     * @return string[]
     */
    public function names(): array
    {
        $names = [$this->resourceChannel->searchIndex];
        foreach (
            $this->resourceChannel->translationLocales as $locale
        ) {
            $names[] = $this->resourceChannel->searchIndex . '-' . $locale;
        }
        return $names;
    }

    /**
     * @throws UnsupportedIndexLanguageException
     */
    private function langToAvailableLocale(
        ResourceChannel $resourceChannel,
        ResourceLanguage $lang,
    ): string {

        if ($lang->code === ResourceLanguage::default()->code) {
            return '';
        }

        $defaultChannelLang = $resourceChannel->locale;
        $sepPos = strpos($defaultChannelLang, '_');
        if ($sepPos !== false) {
            $defaultChannelLang = substr($defaultChannelLang, 0, $sepPos);
        }
        if ($lang->code === $defaultChannelLang) {
            return '';
        }

        if (empty($resourceChannel->translationLocales)) {
            return '';
        }

        foreach (
            $resourceChannel->translationLocales as $availableLocale
        ) {
            if (str_starts_with($availableLocale, $lang->code)) {
                return $availableLocale;
            }
        }
        throw new UnsupportedIndexLanguageException(
            $resourceChannel->searchIndex,
            $lang,
            'No valid index can be determined for the language ' .
            $lang->code,
        );
    }
}
