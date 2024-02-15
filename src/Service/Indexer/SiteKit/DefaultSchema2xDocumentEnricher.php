<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

use Atoolo\Resource\Loader\SiteKitNavigationHierarchyLoader;
use Atoolo\Resource\Resource;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\ContentCollector;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use DateTime;
use Exception;

/**
 * @phpstan-type Phone array{
 *     countryCode?:string,
 *     areaCode?:string,
 *     localNumber?:string
 * }
 * @phpstan-type PhoneData array{phone:Phone}
 * @phpstan-type PhoneList array<PhoneData>
 * @phpstan-type Email array{email:string}
 * @phpstan-type EmailList array<Email>
 * @phpstan-type ContactData array{
 *     phoneList?:PhoneList,
 *     emailList:EmailList
 * }
 * @phpstan-type AddressData array{
 *     buildingName?:string,
 *     street?:string,
 *     postOfficeBoxData?: array{
 *          buildingName?:string
 *     }
 * }
 * @phpstan-type ContactPoint array{
 *     contactData?:ContactData,
 *     addressData?:AddressData
 * }
 * @implements DocumentEnricher<IndexSchema2xDocument>
 */
class DefaultSchema2xDocumentEnricher implements DocumentEnricher
{
    public function __construct(
        private readonly SiteKitNavigationHierarchyLoader $navigationLoader,
        private readonly ContentCollector $contentCollector
    ) {
    }

    public function isIndexable(Resource $resource): bool
    {
        $noIndex = $resource->getData()->getBool('init.noIndex');
        return $noIndex !== true;
    }

    /**
     * @throws DocumentEnrichingException
     */
    public function enrichDocument(
        Resource $resource,
        IndexDocument $doc,
        string $processId
    ): IndexDocument {
        $doc->sp_id = $resource->getId();
        $doc->sp_name = $resource->getName();
        $doc->sp_anchor = $resource->getData()->getString('init.anchor');
        $doc->title = $resource->getData()->getString('base.title');
        $doc->description = $resource->getData()->getString(
            'metadata.description'
        );
        if (empty($doc->description)) {
            $doc->description = $resource->getData()->getString(
                'metadata.intro'
            );
        }
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

        /** @var string[] $spContentType */
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

        /** @var string[] $keyword */
        $keyword = $resource->getData()->getArray('metadata.keywords');
        $doc->keywords = $keyword;

        $doc->sp_boost_keywords = implode(
            ' ',
            $resource->getData()->getArray(
                'metadata.boostKeywords'
            )
        );

        try {
            $sites = $this->getParentSiteGroupIdList($resource);

            $navigationRoot = $this->navigationLoader->loadRoot(
                $resource->getLocation()
            );

            $siteGroupId = $navigationRoot->getData()->getInt(
                'init.siteGroup.id'
            );
            if ($siteGroupId !== 0) {
                $sites[] = (string)$siteGroupId;
            }
            $doc->sp_site = array_unique($sites);
        } catch (Exception $e) {
            throw new DocumentEnrichingException(
                $resource->getLocation(),
                'Unable to set sp_site: ' . $e->getMessage(),
                0,
                $e
            );
        }

        /** @var string[] $wktPrimaryList */
        $wktPrimaryList = $resource->getData()->getArray(
            'base.geo.wkt.primary'
        );
        if (!empty($wktPrimaryList)) {
            $doc->sp_geo_points = $wktPrimaryList;
        }

        /** @var array<array{id: int}> $categoryList */
        $categoryList = $resource->getData()->getArray('metadata.categories');
        if (!empty($categoryList)) {
            $categoryIdList = [];
            foreach ($categoryList as $category) {
                $categoryIdList[] = (string)$category['id'];
            }
            $doc->sp_category = $categoryIdList;
        }

        /** @var array<array{id: int}> $categoryPath */
        $categoryPath = $resource->getData()->getArray(
            'metadata.categoriesPath'
        );
        if (!empty($categoryPath)) {
            $categoryIdPath = [];
            foreach ($categoryPath as $category) {
                $categoryIdPath[] = (string)$category['id'];
            }
            $doc->sp_category_path = $categoryIdPath;
        }

        /** @var array<array{id: int}> $groupPath */
        $groupPath = $resource->getData()->getArray('init.groupPath');
        $groupPathAsIdList = [];
        foreach ($groupPath as $group) {
            $groupPathAsIdList[] = $group['id'];
        }

        $doc->sp_group = $groupPathAsIdList[count($groupPathAsIdList) - 2];
        $doc->sp_group_path = $groupPathAsIdList;

        /** @var array<array{from:int, contentType:string}> $schedulingList */
        $schedulingList = $resource->getData()->getArray('metadata.scheduling');
        if (!empty($schedulingList)) {
            $doc->sp_date = $this->toDateTime($schedulingList[0]['from']);
            $dateList = [];
            $contentTypeList = [];
            foreach ($schedulingList as $scheduling) {
                $contentTypeList[] = explode(' ', $scheduling['contentType']);
                $from = $this->toDateTime($scheduling['from']);
                if ($from !== null) {
                    $dateList[] = $from;
                }
            }
            $doc->sp_contenttype = array_merge(
                $doc->sp_contenttype,
                ...$contentTypeList
            );
            $doc->sp_contenttype = array_unique($doc->sp_contenttype);

            $doc->sp_date_list = $dateList;
        }

        $contentType = $resource->getData()->getString(
            'base.mime',
            'text/html; charset=UTF-8'
        );
        $doc->meta_content_type = $contentType;

        $accessType = $resource->getData()->getString('init.access.type');

        /** @var string[] $groups */
        $groups = $resource->getData()->getArray('init.access.groups');

        if ($accessType === 'allow' && !empty($groups)) {
            $doc->include_groups = array_map(
                fn($id): string => (string)$this->idWithoutSignature($id),
                $groups
            );
        } elseif ($accessType === 'deny' && !empty($groups)) {
            $doc->exclude_groups = array_map(
                fn($id): string => (string)$this->idWithoutSignature($id),
                $groups
            );
        } else {
            $doc->exclude_groups = ['none'];
            $doc->include_groups = ['all'];
        }

        $doc->sp_source = ['internal'];

        return $this->enrichContent($resource, $doc);
    }

    /**
     * @param IndexSchema2xDocument $doc
     * @return IndexSchema2xDocument
     */
    private function enrichContent(
        Resource $resource,
        IndexDocument $doc,
    ): IndexDocument {

        $content = [];
        $content[] = $resource->getData()->getString(
            'searchindexdata.content'
        );

        $content[] = $this->contentCollector->collect(
            $resource->getData()->getArray('content')
        );

        /** @var ContactPoint $contactPoint */
        $contactPoint = $resource->getData()->getArray('metadata.contactPoint');
        $content[] = $this->contactPointToContent($contactPoint);

        /** @var array<array{name?:string}> $categories */
        $categories = $resource->getData()->getArray('metadata.categories');
        foreach ($categories as $category) {
            $content[] = $category['name'] ?? '';
        }

        $cleanContent = preg_replace(
            '/\s+/',
            ' ',
            implode(' ', $content)
        );

        $doc->content = trim($cleanContent ?? '');

        return $doc;
    }

    /**
    * @param ContactPoint $contactPoint
    * @return string
     */
    private function contactPointToContent(array $contactPoint): string
    {
        if (empty($contactPoint)) {
            return '';
        }

        $content = [];
        foreach (($contactPoint['contactData']['phoneList'] ?? []) as $phone) {
            $countryCode = $phone['phone']['countryCode'] ?? '';
            if (
                !empty($countryCode) &&
                !in_array($countryCode, $content, true)
            ) {
                $content[] = '+' . $countryCode;
            }
            $areaCode = $phone['phone']['areaCode'] ?? '';
            if (!empty($areaCode) && !in_array($areaCode, $content, true)) {
                $content[] = $areaCode;
                $content[] = '0' . $areaCode;
            }
            $content[] = ($phone['phone']['localNumber'] ?? '');
        }
        foreach (($contactPoint['contactData']['emailList'] ?? []) as $email) {
            $content[] = $email['email'];
        }

        if (isset($contactPoint['addressData'])) {
            $addressData = $contactPoint['addressData'];
            $content[] = ($addressData['street'] ?? '');
            $content[] = ($addressData['buildingName'] ?? '');
            $content[] = (
                $addressData['postOfficeBoxData']['buildingName'] ??
                ''
            );
        }

        return implode(' ', $content);
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

        /** @var array<array{locale: ?string}> $groupPath */
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

    /**
     * @param Resource $resource
     * @return string[]
     */
    private function getParentSiteGroupIdList(Resource $resource): array
    {
        /** @var array<array{siteGroup: array{id: ?string}}> $parents */
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
