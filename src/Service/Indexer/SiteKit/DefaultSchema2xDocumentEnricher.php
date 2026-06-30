<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\Loader\SiteKitNavigationHierarchyLoader;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\ContentCollector;
use Atoolo\Search\Service\Indexer\DocumentEnricher;
use Atoolo\Search\Service\Indexer\IndexDocument;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use Atoolo\Search\Service\Indexer\SolrIndexService;
use DateTime;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

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
 *     },
 *     notice?:string,
 *     publicTransportationNotice?:string,
 *     accessibleDescription?:string
 * }
 * @phpstan-type ContactPoint array{
 *     contactData?:ContactData,
 *     addressData?:AddressData
 * }
 * @implements DocumentEnricher<IndexSchema2xDocument>
 */
class DefaultSchema2xDocumentEnricher implements DocumentEnricher, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array<string,string> */
    private array $categoryTitleCache = [];

    public function __construct(
        private readonly SiteKitNavigationHierarchyLoader $navigationLoader,
        private readonly ContentCollector $contentCollector,
        private readonly SolrIndexService $indexService,
    ) {}

    public function cleanup(): void
    {
        $this->categoryTitleCache = [];
        $this->navigationLoader->cleanup();
    }

    /**
     * @throws DocumentEnrichingException
     */
    public function enrichDocument(
        Resource $resource,
        IndexDocument $doc,
        string $processId,
    ): IndexDocument {
        $doc->crawl_process_id = $processId;

        $this->enrichCommonFields($resource, $doc);
        $this->enrichCategoryFields($resource, $doc);
        $this->enrichGroupFields($resource, $doc);

        if ($doc->sp_objecttype === 'searchTip') {
            return $doc;
        }
        $this->enrichCommonTextFields($resource, $doc);
        $this->enrichDateFields($resource, $doc);
        $this->enrichNestedEventDocuments($resource, $doc);
        $this->enrichAccessFields($resource, $doc);
        $this->enrichContent($resource, $doc);

        return $doc;
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param Resource $resource
     * @param E $doc
     */
    private function enrichCommonFields(
        Resource $resource,
        IndexDocument $doc,
    ): void {
        /** @var IndexSchema2xDocument $doc */
        $data = $resource->data;
        $base = new DataBag($data->getAssociativeArray('base'));
        $metadata = new DataBag($data->getAssociativeArray('metadata'));

        $doc->id = $resource->id;
        $doc->sp_id = $resource->id;
        $doc->sp_name = $resource->name;
        $doc->sp_anchor = $data->getString('anchor');
        $doc->sp_objecttype = $resource->objectType;
        $doc->sp_canonical = true;
        $doc->sp_source = ['internal'];

        $url = $data->getString('mediaUrl')
            ?: $data->getString('url');
        $doc->url = $url;
        /** @var string[] $keyword */
        $keyword = $metadata->getArray('keywords');
        $doc->keywords = $keyword;
        $doc->sp_boost_keywords = implode(
            ' ',
            $metadata->getArray('boostKeywords'),
        );
        $doc->sp_changed = $this->toDateTime(
            $data->getInt('changed'),
        );
        $doc->sp_generated = $this->toDateTime(
            $data->getInt('generated'),
        );

        $locale = $this->getLocaleFromResource($resource);
        $lang = $this->toLangFromLocale($locale);
        $doc->sp_language = $lang;
        $doc->meta_content_language = $lang;
        $doc->sp_archive = $base->getBool('archive');

        $doc->sp_contenttype = [$resource->objectType];
        if ($data->getBool('media') !== true) {
            $doc->sp_contenttype[] = 'article';
        }

        $contentType = $base->getString(
            'mime',
            'text/html; charset=UTF-8',
        );
        $doc->meta_content_type = $contentType;
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param Resource $resource
     * @param E $doc
     */
    private function enrichCategoryFields(
        Resource $resource,
        IndexDocument $doc,
    ): void {
        /** @var IndexSchema2xDocument $doc */
        $metadata = new DataBag($resource->data->getAssociativeArray('metadata'));
        /** @var array<array{id: int}> $categoryList */
        $categoryList = $metadata->getArray('categories');
        if (!empty($categoryList)) {
            $categoryIdList = [];
            foreach ($categoryList as $category) {
                $categoryIdList[] = (string) $category['id'];
            }
            $doc->sp_category = $categoryIdList;
        }

        /** @var array<array{id: int}> $categoryPath */
        $categoryPath = $metadata->getArray('categoriesPath');
        if (!empty($categoryPath)) {
            $categoryIdPath = [];
            foreach ($categoryPath as $category) {
                $categoryIdPath[] = (string) $category['id'];
            }
            $doc->sp_category_path = $categoryIdPath;
        }
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param Resource $resource
     * @param E $doc
     */
    private function enrichGroupFields(
        Resource $resource,
        IndexDocument $doc,
    ): void {
        /** @var IndexSchema2xDocument $doc */
        /** @var array<array{id: int}> $groupPath */
        $groupPath = $resource->data->getArray('groupPath');
        $groupPathAsIdList = [];
        foreach ($groupPath as $group) {
            $groupPathAsIdList[] = $group['id'];
        }
        if (count($groupPathAsIdList) > 2) {
            $doc->sp_group = $groupPathAsIdList[count($groupPathAsIdList) - 2];
        }
        $doc->sp_group_path = $groupPathAsIdList;

        try {
            $sites = $this->getParentSiteGroupIdList($resource);
            $navigationRoot = $this->navigationLoader->loadRoot(
                $resource->toLocation(),
            );
            $siteGroupId = $navigationRoot->data->getInt(
                'siteGroup.id',
            );
            if ($siteGroupId !== 0) {
                $sites[] = (string) $siteGroupId;
            }
            $doc->sp_site = array_unique($sites);
        } catch (Exception $e) {
            throw new DocumentEnrichingException(
                $resource->toLocation(),
                'Unable to set sp_site: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param Resource $resource
     * @param E $doc
     */
    private function enrichDateFields(
        Resource $resource,
        IndexDocument $doc,
    ): void {
        $base = new DataBag($resource->data->getAssociativeArray('base'));
        $metadata = new DataBag($resource->data->getAssociativeArray('metadata'));

        /** @var IndexSchema2xDocument $doc */
        $doc->sp_date = $this->toDateTime(
            $base->getInt('date'),
        );

        /** @deprecated but still in use for non graphQl search queries */
        if ($doc->sp_date !== null) {
            $doc->sp_date_list = [$doc->sp_date];
        }

        /** @var array<array{from:int, contentType:string}> $schedulingList */
        $schedulingList = $metadata->getArray('scheduling');
        if (!empty($schedulingList)) {
            $dateList = [];
            $contentTypeList = [];

            $now = new \DateTime();
            $currentDay = $now->format('Ymd');

            foreach ($schedulingList as $scheduling) {
                $contentTypeList[] = explode(' ', $scheduling['contentType']);
                $from = $this->toDateTime($scheduling['from']);
                if ($from !== null) {
                    $day = $from->format('Ymd');
                    if ($day >= $currentDay) {
                        $dateList[] = $from;
                    }
                }
            }
            $doc->sp_contenttype = array_merge(
                $doc->sp_contenttype ?? [],
                ...$contentTypeList,
            );
            $doc->sp_contenttype = array_unique($doc->sp_contenttype);

            if (count($dateList) > 0) {
                $doc->sp_date = $dateList[0];
            }
            /** @deprecated but still in use for non graphQl search queries */
            $doc->sp_date_list = $dateList;
        }
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param Resource $resource
     * @param E $doc
     */
    private function enrichAccessFields(
        Resource $resource,
        IndexDocument $doc,
    ): void {
        /** @var string[] $groups */
        $groups = $resource->data->getArray('access.groups');

        $accessType = $resource->data->getString('access.type');
        if ($accessType === 'allow' && !empty($groups)) {
            $doc->include_groups = array_map(
                fn($id): string => (string) $this->idWithoutSignature($id),
                $groups,
            );
            $doc->include_groups[] = 'admin';
        } elseif ($accessType === 'deny' && !empty($groups)) {
            $doc->exclude_groups = array_map(
                fn($id): string => (string) $this->idWithoutSignature($id),
                $groups,
            );
        } else {
            $doc->exclude_groups = ['none'];
            $doc->include_groups = ['all'];
        }
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param Resource $resource
     * @param E $doc
     */
    private function enrichCommonTextFields(
        Resource $resource,
        IndexDocument $doc,
    ): void {

        $data = $resource->data;
        $base = new DataBag($data->getAssociativeArray('base'));
        $metadata = new DataBag($data->getAssociativeArray('metadata'));

        $doc->title = $base->getString('title');
        $doc->description = $metadata->getString('intro', $metadata->getString('description'));

        /** @var string[] $spContentType */
        $spContentType = $data->getArray('contentSectionTypes');
        if ($base->has('teaser.image')) {
            $spContentType[] = 'teaserImage';
        }
        if ($base->has('teaser.image.copyright')) {
            $spContentType[] = 'teaserImageCopyright';
        }
        if ($base->has('teaser.headline')) {
            $spContentType[] = 'teaserHeadline';
        }
        if ($base->has('teaser.text')) {
            $spContentType[] = 'teaserText';
        }
        $doc->sp_contenttype = array_merge(
            $doc->sp_contenttype ?? [],
            $spContentType,
        );

        $headline = $base->getString('teaser.headline')
            ?: $metadata->getString('headline')
            ?: $base->getString('title');
        $doc->sp_title = $headline;

        $sortHeadline = $base->getString('teaser.headline')
            ?: $metadata->getString('headline')
            ?: $base->getString('title');
        $doc->sp_sortvalue = $sortHeadline;

        if ($base->has('startletter')) {
            $doc->sp_startletter = $base->getString('startletter');
        }

        /** @var string[] $wktPrimaryList */
        $wktPrimaryList = $base->getArray('geo.wkt.primary');
        if (!empty($wktPrimaryList)) {
            $doc->sp_geo_points = $wktPrimaryList;
        }
    }

    /**
     * @template E of IndexSchema2xDocument
     * @param Resource $resource
     * @param E $doc
     * @return E
     */
    private function enrichContent(
        Resource $resource,
        IndexDocument $doc,
    ): IndexDocument {

        $content = [];
        $content[] = $resource->data->getString(
            'searchindexdata.content',
        );

        $content[] = $this->contentCollector->collect(
            $resource->data->getArray('content'),
            $resource,
        );

        /** @var ContactPoint $contactPoint */
        $contactPoint = $resource->data->getArray('metadata.contactPoint');
        $content[] = $this->contactPointToContent($contactPoint);

        /** @var array<array{name?:string,url?:string}> $categories */
        $categories = $resource->data->getArray('metadata.categories');
        foreach ($categories as $category) {
            $categoryUrl = $category['url'] ?? null;
            $categoryTitle = $categoryUrl !== null
                ? $this->loadCategoryTitle($categoryUrl, $resource->lang)
                : '';
            $content[] = !empty($categoryTitle)
                ? $categoryTitle
                : ($category['name'] ?? '');
        }

        $cleanContent = preg_replace(
            '/\s+/',
            ' ',
            implode(' ', $content),
        );

        $doc->content = trim($cleanContent ?? '');

        return $doc;
    }

    private function loadCategoryTitle(
        string $url,
        ResourceLanguage $lang,
    ): string {
        $cacheKey = $url . ':' . $lang->code;
        if (array_key_exists($cacheKey, $this->categoryTitleCache)) {
            return $this->categoryTitleCache[$cacheKey];
        }

        $title = '';
        try {
            $categoryResource = $this->navigationLoader->load(
                ResourceLocation::of($url, $lang),
            );
            $title = $categoryResource->data->getString('base.title', '');
        } catch (\Throwable $th) {
            $this->logger?->error(
                sprintf('unable to load category with url "%s"', $url),
                [
                    'error' => $th,
                    'url' => $url,
                ],
            );
        }

        $this->categoryTitleCache[$cacheKey] = $title;
        return $title;
    }

    /**
     * Add nested documents for each event-date in _nest_path_: 'sp_date_documents'.
     * @template E of IndexSchema2xDocument
     * @param Resource $resource
     * @param E $doc
     */
    private function enrichNestedEventDocuments(
        Resource $resource,
        IndexDocument $doc,
    ): void {
        $metadata = new DataBag($resource->data->getAssociativeArray('metadata'));
        /** @var array<array{from:int, to?:int, contentType:string}> $schedulingList */
        $schedulingList = $metadata->getArray('scheduling');
        if (empty($schedulingList)) {
            return;
        }
        $doc->sp_date_documents = [];
        $now = new \DateTime();
        $currentDay = $now->format('Ymd');
        foreach ($schedulingList as $scheduling) {
            $from = $this->toDateTime($scheduling['from']);
            $to = isset($scheduling['to']) ? $this->toDateTime($scheduling['to']) : $from;
            if ($from !== null && $from->format('Ymd') >= $currentDay) {
                /** @var IndexSchema2xDocument $dateChild */
                $dateChild = $this->indexService->updater($resource->lang)->createDocument();
                $doc->sp_date_documents[] = $dateChild;

                $this->enrichCommonFields($resource, $dateChild);
                $this->enrichCommonTextFields($resource, $dateChild);
                $this->enrichCategoryFields($resource, $dateChild);
                $this->enrichGroupFields($resource, $dateChild);
                $this->enrichContent($resource, $dateChild);
                $this->enrichAccessFields($resource, $dateChild);

                $dateChild->id = $dateChild->id . '-' . $from->getTimestamp();
                $dateChild->url = $dateChild->url . '?date=' . $from->getTimestamp();
                $dateChild->sp_date =  $from;

                $dateChild->sp_date_from = $from;
                $dateChild->sp_date_to = $to;
            }
        }
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
                !empty($countryCode)
                && !in_array($countryCode, $content, true)
            ) {
                $content[] = '+' . $countryCode;
            }
            $areaCode = $phone['phone']['areaCode'] ?? '';
            if (!empty($areaCode) && !in_array($areaCode, $content, true)) {
                $content[] = $areaCode;
                $content[] = '0' . $areaCode;
            }
            $content[] = $phone['phone']['localNumber'] ?? '';
        }
        foreach ($contactPoint['contactData']['emailList'] ?? [] as $email) {
            $content[] = $email['email'];
        }

        if (isset($contactPoint['addressData'])) {
            $data = $contactPoint['addressData'];
            $content[] = $data['street'] ?? '';
            $content[] = $data['buildingName'] ?? '';
            $content[] = $data['postOfficeBoxData']['buildingName'] ?? '';
            $content[] = $data['notice'] ?? '';
            $content[] = $data['publicTransportationNotice'] ?? '';
            $content[] = $data['accessibleDescription'] ?? '';
        }

        return implode(' ', $content);
    }

    private function idWithoutSignature(string $id): int
    {
        $s = substr($id, -11);
        return (int) $s;
    }

    private function getLocaleFromResource(Resource $resource): string
    {

        $locale = $resource->data->getString('locale');
        if ($locale !== '') {
            return $locale;
        }

        /** @var array<array{locale: ?string}> $groupPath */
        $groupPath = $resource->data->getArray('groupPath');
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
        return $resource->data->getAssociativeArray(
            'base.trees.navigation.parents',
        );
    }
}
