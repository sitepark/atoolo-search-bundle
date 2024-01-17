<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

use Atoolo\Resource\Loader\SiteKitNavigationHierarchyLoader;
use Atoolo\Resource\Resource;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use DateTime;
use Solarium\Core\Query\DocumentInterface;

class DefaultSchema21DocumentEnricher implements DocumentEnricher
{
    public function __construct(
        private readonly SiteKitNavigationHierarchyLoader $navigationLoader
    ) {
    }

    public function isIndexable(Resource $resource): bool
    {
        $noIndex = $resource->getData()->getBool('init.noIndex');
        return $noIndex !== true;
    }
    public function enrichDocument(
        Resource $resource,
        DocumentInterface $doc,
        string $processId
    ): DocumentInterface {
        $doc->sp_id = $resource->getId();
        $doc->sp_name = $resource->getName();
        $doc->sp_anchor = $resource->getData()->getString('init.anchor');
        $doc->title = $resource->getData()->getString('base.title');
        $doc->description = $resource->getData()->getString(
            'metadata.description'
        );
        $doc->sp_objecttype = $resource->getObjectType();
        $doc->sp_canonical = true;
        $doc->crawl_process_id = $processId;

        $mediaUrl = $resource->getData()->getString('init.mediaUrl');
        if (!empty($mediaUrl)) {
            $doc->id = $mediaUrl;
            $doc->url = $mediaUrl;
        } else {
            $url = $resource->getData()->getString('init.url');
            $doc->id = $url;
            $doc->url = $url;
        }

        $spContentType = [$resource->getObjectType()];
        if ($resource->getData()->getBool('init.media') !== true) {
            $spContentType[] = 'article';
        }
        $contentSectionTypes = $resource->getData()->getArray(
            'init.contentSectionTypes'
        );
        $spContentType = array_merge($spContentType, $contentSectionTypes);

        if ($resource->getData()->has('base.teaser.image')) {
            $spContentType[] = 'teaserImage';
        }
        if ($resource->getData()->has('base.teaser.image.copyright')) {
            $spContentType[] = 'teaserImageCopyright';
        }
        if ($resource->getData()->has('base.teaser.headline')) {
            $spContentType[] = 'teaserHeadline';
        }
        if ($resource->getData()->has('base.teaser.text')) {
            $spContentType[] = 'teaserText';
        }
        $doc->sp_contenttype = $spContentType;

        $locale = $this->getLocaleFromResource($resource);
        $lang = $this->toLangFromLocale($locale);
        $doc->sp_language = $lang;
        $doc->meta_content_language = $lang;

        $doc->sp_changed = $this->toDateTime(
            $resource->getData()->getInt('init.changed')
        );
        $doc->sp_generated = $this->toDateTime(
            $resource->getData()->getInt('init.generated')
        );
        $doc->sp_date = $this->toDateTime(
            $resource->getData()->getInt('base.date')
        );

        $doc->sp_archive = $resource->getData()->getBool('base.archive');

        $headline = $resource->getData()->getString('metadata.headline');
        if (empty($headline)) {
            $headline = $resource->getData()->getString('base.teaser.headline');
        }
        if (empty($headline)) {
            $headline = $resource->getData()->getString('base.title');
        }
        $doc->sp_title = $headline;

        // However, the teaser heading, if specified, must be used for sorting
        $sortHeadline = $resource->getData()->getString('base.teaser.headline');
        if (empty($sortHeadline)) {
            $sortHeadline = $resource->getData()->getString(
                'metadata.headline'
            );
        }
        if (empty($sortHeadline)) {
            $sortHeadline = $resource->getData()->getString('base.title');
        }
        $doc->sp_sortvalue = $sortHeadline;

        $doc->keywords = $resource->getData()->getArray('metadata.keywords');

        $doc->sp_boost_keywords = $resource->getData()->getArray(
            'metadata.boostKeywords'
        );

        $sites = $this->getParentSiteGroupIdList($resource);

        $navigationRoot = $this->navigationLoader->loadRoot(
            $resource->getLocation()
        );
        $siteGroupId = $navigationRoot->getData()->getInt('init.siteGroup.id');
        if ($siteGroupId !== 0) {
            $sites[] = $siteGroupId;
        }
        $doc->sp_site = array_unique($sites);

        $wktPrimaryList = $resource->getData()->getArray(
            'base.geo.wkt.primary'
        );
        if (!empty($wktPrimaryList)) {
            $allWkt = [];
            foreach ($wktPrimaryList as $wkt) {
                $allWkt[] = $wkt;
            }
            if (count($allWkt) > 0) {
                $doc->sp_geo_points = $allWkt;
            }
        }

        $categoryList = $resource->getData()->getArray('metadata.categories');
        if (!empty($categoryList)) {
            $categoryIdList = [];
            foreach ($categoryList as $category) {
                $categoryIdList[] = $category['id'];
            }
            $doc->sp_category = $categoryIdList;
        }

        $categoryPath = $resource->getData()->getArray(
            'metadata.categoriesPath'
        );
        if (!empty($categoryPath)) {
            $categoryIdPath = [];
            foreach ($categoryPath as $category) {
                $categoryIdPath[] = $category['id'];
            }
            $doc->sp_category_path = $categoryIdPath;
        }

        $groupPath = $resource->getData()->getArray('init.groupPath');
        $groupPathAsIdList = [];
        foreach ($groupPath as $group) {
            $groupPathAsIdList[] = $group['id'];
        }

        $doc->sp_group = $groupPathAsIdList[count($groupPathAsIdList) - 2];
        $doc->sp_group_path = $groupPathAsIdList;

        $schedulingList = $resource->getData()->getArray('metadata.scheduling');
        if (!empty($schedulingList)) {
            $doc->sp_date = $this->toDateTime($schedulingList[0]['from']);
            $dateList = [];
            $contentTypeList = [];
            foreach ($schedulingList as $scheduling) {
                $contentTypeList[] = explode(' ', $scheduling['contentType']);
                $dateList[] = $this->toDateTime($scheduling['from']);
            }
            $doc->sp_contenttype = array_merge(
                $doc->sp_contenttype,
                ...$contentTypeList
            );
            $doc->sp_contenttype = array_unique($doc->sp_contenttype);

            $doc->sp_date_list = $dateList;
        }

        $contentType = $resource->getData()->getString('base.mime');
        if ($contentType === null) {
            $contentType = 'text/html; charset=UTF-8';
        }
        $doc->meta_content_type = $contentType;
        $doc->content = $resource->getData()->getString(
            'searchindexdata.content'
        );

        $accessType = $resource->getData()->getString('init.access.type');
        $groups = $resource->getData()->getArray('init.access.groups');


        if ($accessType === 'allow' && !empty($groups)) {
            $doc->include_groups = array_map(
                fn($id): int => $this->idWithoutSignature($id),
                $groups
            );
        } elseif ($accessType === 'deny' && !empty($groups)) {
            $doc->exclude_groups = array_map(
                fn($id): int => $this->idWithoutSignature($id),
                $groups
            );
        } else {
            $doc->exclude_groups = ['none'];
            $doc->include_groups = ['all'];
        }

        $doc->sp_source = ['internal'];

        return $doc;
    }

    private function idWithoutSignature(string $id): int
    {
        $s = substr($id, -11);
        return (int)$s;
    }

    /* Customization
     * - https://gitlab.sitepark.com/customer-projects/fhdo/blob/develop/fhdo-module/src/publish/php/SP/Fhdo/Component/Content/DetailPage/StartletterIndexSupplier.php#L31
     * - https://gitlab.sitepark.com/apis/sitekit-php/blob/develop/php/SP/SiteKit/Component/Content/NewsdeskRss.php#L235
     * - https://gitlab.sitepark.com/customer-projects/fhdo/blob/develop/fhdo-module/src/publish/php/SP/Fhdo/Component/SearchMetadataExtension.php#L41
     * - https://gitlab.sitepark.com/customer-projects/paderborn/blob/develop/paderborn-module/src/publish/php/SP/Paderborn/Component/FscEntity.php#L67
     * - https://gitlab.sitepark.com/customer-projects/paderborn/blob/develop/paderborn-module/src/publish/php/SP/Paderborn/Component/FscContactPerson.php#L24
     * - https://gitlab.sitepark.com/customer-projects/stadtundland/blob/develop/stadtundland-module/src/publish/php/SP/Stadtundland/Component/ParkingSpaceExpose.php#L38
     * - https://gitlab.sitepark.com/customer-projects/stadtundland/blob/develop/stadtundland-module/src/publish/php/SP/Stadtundland/Component/Expose.php#L38
     * - https://gitlab.sitepark.com/customer-projects/stadtundland/blob/develop/stadtundland-module/src/publish/php/SP/Stadtundland/Component/PurchaseExpose.php#L38
     * - https://gitlab.sitepark.com/customer-projects/stuttgart/blob/develop/stuttgart-module/src/publish/php/SP/Stuttgart/Component/EventsCalendarExtension.php#L124
     * - https://gitlab.sitepark.com/ies-modules/citycall/blob/develop/citycall-module/src/main/php/src/SP/CityCall/Component/Intro.php#L51
     * - https://gitlab.sitepark.com/ies-modules/citycall/blob/develop/citycall-module/src/main/php/src/SP/CityCall/Controller/Environment.php#L76
     * - https://gitlab.sitepark.com/ies-modules/sitekit-real-estate/blob/develop/src/publish/php/SP/RealEstate/Component/Expose.php#L47
     */

    private function getLocaleFromResource(Resource $resource): string
    {

        $locale = $resource->getData()->getString('init.locale');
        if ($locale !== '') {
            return $locale;
        }
        $groupPath = $resource->getData()->getArray('init.groupPath');
        if (!empty($groupPath)) {
            $len = count($groupPath);
            for ($i = $len - 1; $i >= 0; $i--) {
                $group = $groupPath[$i];
                if (isset($group['locale'])) {
                    return $group['locale'];
                }
            }
        }

        return 'de_DE';
    }

    private function toLangFromLocale(string $locale): string
    {
        if (str_contains($locale, '_')) {
            $parts = explode('_', $locale);
            return $parts[0];
        }
        return $locale;
    }

    private function toDateTime(int $timestamp): ?DateTime
    {
        if ($timestamp <= 0) {
            return null;
        }

        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);
        return $dateTime;
    }

    private function getParentSiteGroupIdList(Resource $resource): array
    {
        $parents = $this->getNavigationParents($resource);
        if (empty($parents)) {
            return [];
        }

        $siteGroupIdList = [];
        foreach ($parents as $parent) {
            if (isset($parent['siteGroup']['id'])) {
                $siteGroupIdList[] = $parent['siteGroup']['id'];
            }
        }

        return $siteGroupIdList;
    }

    /**
     * @return array<string,mixed>
     */
    private function getNavigationParents(Resource $resource): array
    {
        return $resource->getData()->getAssociativeArray(
            'base.trees.navigation.parents'
        );
    }
}
