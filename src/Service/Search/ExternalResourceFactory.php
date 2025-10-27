<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use LogicException;
use Solarium\QueryType\Select\Result\Document;

/**
 * External resources are data that is not provided by the CMS, but are
 * transferred to the Solr index via special indexers, for example.
 * In these cases, the search result leads to an external page.
 * This factory recognizes external content using the Solr document field url.
 * It is an external resource if the URL begins with https:// or http://.
 */
class ExternalResourceFactory implements ResourceFactory
{
    public function accept(Document $document, ResourceLanguage $lang): bool
    {
        $location = $this->getField($document, 'url');
        if ($location === '') {
            return false;
        }
        return (
            str_starts_with($location, 'http://') ||
            str_starts_with($location, 'https://')
        );
    }

    public function create(Document $document, ResourceLanguage $lang): Resource
    {
        $location = $this->getField($document, 'url');
        if ($location === '') {
            throw new LogicException('document should contain an url');
        }
        $title = $this->getField($document, 'title');
        $data = [
            'base' => [
                'teaser' => [
                    'text' => $this->getField($document, 'description'),
                ],
            ],
            'metadata' => [
                'headline' => $title,
                'schedulingRaw' => $this->createSchedulingRaw($document),
            ],
        ];
        $kicker = $this->getField($document, 'sp_meta_string_kicker');
        if (!empty($kicker)) {
            $data['base']['kicker'] = $kicker;
        }

        return new Resource(
            location: $location,
            id: $this->getField($document, 'id'),
            name: $title,
            objectType: $this->getField(
                $document,
                'sp_objecttype',
                'external',
            ),
            lang: ResourceLanguage::of(
                $this->getField($document, 'meta_content_language'),
            ),
            data: new DataBag($data),
        );
    }

    private function getField(
        Document $document,
        string $name,
        string $default = '',
    ): string {
        $value = $document->getFields()[$name] ?? null;
        if ($value === null) {
            return $default;
        }

        if (is_array($value)) {
            return implode(' ', $value);
        }

        return (string) $value;
    }

    /**
     * Creates a "schedulingRaw" array similar to the data that is
     * aggregated by eventsCalendar-events.
     * We can not infer any repition rules yet, as the solr
     * document does not contain enough information.
     * Instead, this function just takes each date from sp_date_list and
     * sets it as a single-occurence scheduling date.
     *
     * @return array<array<string,mixed>>
     */
    protected function createSchedulingRaw(Document $document): array
    {
        $list = $document->getFields()['sp_date_list'] ?? [];

        $rawScheduling = [];
        foreach ($list as $dateStartRaw) {
            $dateStart = new \DateTime($dateStartRaw);
            $dateStartUnix = $dateStart->getTimestamp();
            $dateStartUnixWithouTime = $dateStartUnix - ($dateStartUnix % (60 * 60 * 24));
            $rawScheduling[] = [
                'type' => 'single',
                'isFullDay' => false,
                'beginDate' => $dateStartUnixWithouTime,
                'beginTime' => $dateStart->format('H:i'),
            ];
        }
        return $rawScheduling;
    }
}
