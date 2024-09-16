<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer\SiteKit;

use Atoolo\Resource\DataBag;
use Atoolo\Resource\Exception\InvalidResourceException;
use Atoolo\Resource\Loader\SiteKitNavigationHierarchyLoader;
use Atoolo\Resource\Resource;
use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Exception\DocumentEnrichingException;
use Atoolo\Search\Service\Indexer\ContentCollector;
use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use Atoolo\Search\Service\Indexer\SiteKit\DefaultSchema2xDocumentEnricher;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DefaultSchema2xDocumentEnricherTest extends TestCase
{
    private DefaultSchema2xDocumentEnricher $enricher;

    private SiteKitNavigationHierarchyLoader&MockObject $navigationLoader;

    public function setUp(): void
    {
        $this->navigationLoader = $this->createMock(
            SiteKitNavigationHierarchyLoader::class,
        );
        $this->navigationLoader
            ->method('loadRoot')
            ->willReturnCallback(function ($location) {
                if ($location->location === 'throwException') {
                    throw new InvalidResourceException($location);
                }
                return $this->createResource([
                    'siteGroup' => ['id' => 999],
                ]);
            });
        $contentCollector = $this->createStub(ContentCollector::class);
        $contentCollector
            ->method('collect')
            ->willReturn('collected content');

        $this->enricher = new DefaultSchema2xDocumentEnricher(
            $this->navigationLoader,
            $contentCollector,
        );
    }

    public function testCleanup(): void
    {
        $this->navigationLoader->expects($this->once())
            ->method('cleanup');
        $this->enricher->cleanup();
    }

    public function testEnrichSpId(): void
    {
        $resource = new Resource(
            '',
            '123',
            '',
            '',
            ResourceLanguage::default(),
            new DataBag([]),
        );
        $doc = $this->enrichWithResource($resource);
        $this->assertEquals('123', $doc->sp_id, 'unexpected id');
    }

    public function testEnrichName(): void
    {
        $resource = $this->createResource([
            'name' => 'test',
        ]);
        $doc = $this->enrichWithResource($resource);
        $this->assertEquals('test', $doc->sp_name, 'unexpected name');
    }

    public function testEnrichObjectType(): void
    {
        $resource = $this->createResource([
            'objectType' => 'test',
        ]);
        $doc = $this->enrichWithResource($resource);
        $this->assertEquals(
            'test',
            $doc->sp_objecttype,
            'unexpected objectType',
        );
    }

    public function testEnrichAnchor(): void
    {
        $doc = $this->enrichWithData(['anchor' => 'abc']);
        $this->assertEquals('abc', $doc->sp_anchor, 'unexpected ancohr');
    }

    public function testEnrichTitle(): void
    {
        $doc = $this->enrichWithData(['base' => ['title' => 'abc']]);
        $this->assertEquals('abc', $doc->title, 'unexpected title');
    }

    public function testEnrichDescriptionWithInto(): void
    {
        $doc = $this->enrichWithData(['metadata' => ['intro' => 'abc']]);
        $this->assertEquals(
            'abc',
            $doc->description,
            'unexpected description',
        );
    }

    public function testEnrichCanonical(): void
    {
        $doc = $this->enrichWithData([]);
        $this->assertTrue(
            $doc->sp_canonical,
            'unexpected canonical',
        );
    }

    public function testEnrichCrawlProcessId(): void
    {
        $resource = $this->createResource([]);
        $doc = $this->enricher->enrichDocument(
            $resource,
            new IndexSchema2xDocument(),
            'progress-id',
        );
        $this->assertEquals(
            $doc->crawl_process_id,
            'progress-id',
            'unexpected progress id',
        );
    }

    public function testEnrichId(): void
    {
        $doc = $this->enrichWithData(['id' => '123']);
        $this->assertEquals(
            '123',
            $doc->id,
            'unexpected id',
        );
    }

    public function testEnrichUrl(): void
    {
        $doc = $this->enrichWithData(['url' => '/test.php']);
        $this->assertEquals(
            '/test.php',
            $doc->url,
            'unexpected url',
        );
    }

    public function testEnrichMediaUrl(): void
    {
        $doc = $this->enrichWithData(['mediaUrl' => '/test.php']);
        $this->assertEquals(
            '/test.php',
            $doc->url,
            'unexpected url',
        );
    }

    public function testEnrichSpContentType(): void
    {
        $doc = $this->enrichWithData([
            'objectType' => 'content',
            'contentSectionTypes' => ['text', 'linkList'],
            'base' => [
                'teaser' => [
                    'headline' => 'test',
                    'image' => [
                        'copyright' => 'test',
                    ],
                    'text' => 'test',
                ],
            ],
        ]);
        $this->assertEquals(
            [
                'content',
                'article',
                'text',
                'linkList',
                'teaserImage',
                'teaserImageCopyright',
                'teaserHeadline',
                'teaserText',
            ],
            $doc->sp_contenttype,
            'unexpected sp_contenttype',
        );
    }

    public function testEnrichDefaultLanguage(): void
    {
        $doc = $this->enrichWithData([]);
        $this->assertEquals(
            'de',
            $doc->sp_language,
            'unexpected language',
        );
    }

    public function testEnrichLanguage(): void
    {
        $doc = $this->enrichWithData(['locale' => 'en_US']);
        $this->assertEquals(
            'en',
            $doc->sp_language,
            'unexpected language',
        );
    }

    public function testEnrichLanguageWithShortLocale(): void
    {
        $doc = $this->enrichWithData(['locale' => 'en']);
        $this->assertEquals(
            'en',
            $doc->sp_language,
            'unexpected language',
        );
    }

    public function testEnrichLanguageOverGroupPath(): void
    {
        $doc = $this->enrichWithData([
            'groupPath' => [
                ['id' => 1, 'locale' => 'fr_FR'],
                ['id' => 2, 'locale' => 'it_IT'],
            ],
        ]);
        $this->assertEquals(
            'it',
            $doc->sp_language,
            'unexpected language',
        );
    }

    public function testEnrichDefaultMetaContentLanguage(): void
    {
        $doc = $this->enrichWithData([]);
        $this->assertEquals(
            'de',
            $doc->meta_content_language,
            'unexpected language',
        );
    }

    public function testEnrichChanged(): void
    {
        $doc = $this->enrichWithData(['changed' => 1708932236]);
        $expected = new DateTime();
        $expected->setTimestamp(1708932236);

        $this->assertEquals(
            $expected,
            $doc->sp_changed,
            'unexpected sp_changed',
        );
    }

    public function testEnrichGenerated(): void
    {
        $doc = $this->enrichWithData(['generated' => 1708932236]);
        $expected = new DateTime();
        $expected->setTimestamp(1708932236);

        $this->assertEquals(
            $expected,
            $doc->sp_generated,
            'unexpected sp_generated',
        );
    }

    public function testEnrichDate(): void
    {
        $doc = $this->enrichWithData(['base' => ['date' => 1708932236]]);
        $expected = new DateTime();
        $expected->setTimestamp(1708932236);

        $this->assertEquals(
            $expected,
            $doc->sp_date,
            'unexpected sp_generated',
        );
    }

    public function testEnrichArchive(): void
    {
        $doc = $this->enrichWithData(['base' => ['archive' => true]]);
        $this->assertTrue(
            $doc->sp_archive,
            'unexpected language',
        );
    }

    public function testEnrichSpTitle(): void
    {
        $doc = $this->enrichWithData(['metadata' => ['headline' => 'test']]);
        $this->assertEquals(
            'test',
            $doc->sp_title,
            'unexpected sp_title',
        );
    }

    public function testEnrichSpTitleWithTeaserHeadlineFallback(): void
    {
        $doc = $this->enrichWithData(['base' => [
            'title' => 'test',
            'teaser' => [
                'headline' => 'test',
            ],
        ]]);
        $this->assertEquals(
            'test',
            $doc->sp_title,
            'unexpected sp_title',
        );
    }

    public function testEnrichSpTitleWithTeaserTitleFallback(): void
    {
        $doc = $this->enrichWithData(['base' => ['title' => 'test']]);
        $this->assertEquals(
            'test',
            $doc->sp_title,
            'unexpected sp_title',
        );
    }

    public function testEnrichSortValue(): void
    {
        $doc = $this->enrichWithData(['base' => [
            'teaser' => [
                'headline' => 'test',
            ],
        ]]);
        $this->assertEquals(
            'test',
            $doc->sp_sortvalue,
            'unexpected sp_sortvalue',
        );
    }

    public function testEnrichSortValueWithHeadlineFallback(): void
    {
        $doc = $this->enrichWithData(['base' => [
            'title' => 'test',
            'teaser' => [
                'headline' => 'test',
            ],
        ]]);
        $this->assertEquals(
            'test',
            $doc->sp_sortvalue,
            'unexpected sp_sortvalue',
        );
    }

    public function testEnrichSortValueWithTitleFallback(): void
    {
        $doc = $this->enrichWithData(['base' => ['title' => 'test']]);
        $this->assertEquals(
            'test',
            $doc->sp_sortvalue,
            'unexpected sp_sortvalue',
        );
    }

    public function testEnrichKeywords(): void
    {
        $doc = $this->enrichWithData(['metadata' => [
            'keywords' => ['abc', 'cde'],
        ]]);
        $this->assertEquals(
            ['abc', 'cde'],
            $doc->keywords,
            'unexpected keywords',
        );
    }

    public function testEnrichBoostKeywords(): void
    {
        $doc = $this->enrichWithData(['metadata' => [
            'boostKeywords' => ['abc', 'cde'],
        ]]);
        $this->assertEquals(
            'abc cde',
            $doc->sp_boost_keywords,
            'unexpected keywords',
        );
    }

    public function testEnrichSpSites(): void
    {
        $doc = $this->enrichWithData(['base' => [
            'trees' => [
                'navigation' => [
                    'parents' => [
                        ['siteGroup' => ['id' => '123']],
                        ['siteGroup' => ['id' => '456']],
                    ],
                ],
            ],
        ]]);
        $this->assertEquals(
            ['123', '456', '999'],
            $doc->sp_site,
            'unexpected keywords',
        );
    }

    public function testEnrichSpSitesWithInvalidRootResource(): void
    {
        $resource = $this->createResource([
            'url' => 'throwException',
        ]);

        $this->expectException(DocumentEnrichingException::class);
        $this->enrichWithResource($resource);
    }

    public function testEnrichGeoPoints(): void
    {
        $doc = $this->enrichWithData(['base' => [
            'geo' => [
                'wkt' => [
                    'primary' => [
                        'value' => 'test',
                    ],
                ],
            ],
        ]]);
        $this->assertEquals(
            ['value' => 'test'],
            $doc->sp_geo_points,
            'unexpected sp_geo_points',
        );
    }

    public function testEnrichCategories(): void
    {
        $doc = $this->enrichWithData(['metadata' => [
            'categories' => [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ],
        ]]);
        $this->assertEquals(
            ['1', '2', '3'],
            $doc->sp_category,
            'unexpected sp_category',
        );
    }

    public function testEnrichCategoryPath(): void
    {
        $doc = $this->enrichWithData(['metadata' => [
            'categoriesPath' => [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ],
        ]]);
        $this->assertEquals(
            ['1', '2', '3'],
            $doc->sp_category_path,
            'unexpected sp_category_path',
        );
    }

    public function testEnrichSpGroup(): void
    {
        $doc = $this->enrichWithData([
            'groupPath' => [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ],
        ]);
        $this->assertEquals(
            2,
            $doc->sp_group,
            'unexpected sp_group',
        );
    }

    public function testEnrichSpGroupPath(): void
    {
        $doc = $this->enrichWithData([
            'groupPath' => [
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ],
        ]);
        $this->assertEquals(
            [1, 2, 3],
            $doc->sp_group_path,
            'unexpected sp_group_path',
        );
    }

    public function testEnrichDateViaScheduling(): void
    {
        $doc = $this->enrichWithData([
            'base' => ['date' => 1707549836],
            'metadata' => [
                'scheduling' => [
                    ['from' => 1708932236, 'contentType' => 'test'],
                    ['from' => 1709105036, 'contentType' => 'test'],
                ],
            ],
        ]);

        $expected = new DateTime();
        $expected->setTimestamp(1708932236);

        $this->assertEquals(
            $expected,
            $doc->sp_date,
            'unexpected sp_date',
        );
    }

    public function testEnrichContentTypeViaScheduling(): void
    {
        $doc = $this->enrichWithData([
            'objectType' => 'content',
            'base' => ['date' => 1707549836],
            'metadata' => [
                'scheduling' => [
                    ['from' => 1708932236, 'contentType' => 'test1'],
                    ['from' => 1709105036, 'contentType' => 'test2'],
                    ['from' => 1707981836, 'contentType' => 'test1'],
                ],
            ],
        ]);

        $this->assertEquals(
            ['content', 'article', 'test1', 'test2'],
            $doc->sp_contenttype,
            'unexpected sp_contenttype',
        );
    }

    public function testEnrichDateListViaScheduling(): void
    {
        $doc = $this->enrichWithData([
            'metadata' => [
                'scheduling' => [
                    ['from' => 1708932236, 'contentType' => 'test'],
                    ['from' => 1709105036, 'contentType' => 'test'],
                ],
            ],
        ]);

        $dateA = new DateTime();
        $dateA->setTimestamp(1708932236);
        $dateB = new DateTime();
        $dateB->setTimestamp(1709105036);

        $this->assertEquals(
            [$dateA, $dateB],
            $doc->sp_date_list,
            'unexpected sp_date',
        );
    }

    public function testEnrichDefaultMetaContentType(): void
    {
        $doc = $this->enrichWithData([]);

        $this->assertEquals(
            'text/html; charset=UTF-8',
            $doc->meta_content_type,
            'unexpected meta_content_type',
        );
    }

    public function testEnrichIncludeGroups(): void
    {
        $doc = $this->enrichWithData([
            'access' => [
                'type' => 'allow',
                'groups' => ['100010100000001028'],
            ],
        ]);

        $this->assertEquals(
            ['1028'],
            $doc->include_groups,
            'unexpected include_groups',
        );
    }

    public function testEnrichIncludeAllGroups(): void
    {
        $doc = $this->enrichWithData([]);

        $this->assertEquals(
            ['all'],
            $doc->include_groups,
            'unexpected include_groups',
        );
    }

    public function testEnrichExcludeGroups(): void
    {
        $doc = $this->enrichWithData([
            'access' => [
                'type' => 'deny',
                'groups' => ['100010100000001028'],
            ],
        ]);

        $this->assertEquals(
            ['1028'],
            $doc->exclude_groups,
            'unexpected exclude_groups',
        );
    }

    public function testEnrichNonExcludeGroups(): void
    {
        $doc = $this->enrichWithData([]);

        $this->assertEquals(
            ['none'],
            $doc->exclude_groups,
            'unexpected exclude_groups',
        );
    }

    public function testEnrichMetaContentType(): void
    {
        $doc = $this->enrichWithData([
            'base' => ['mime' => 'application/pdf'],
        ]);

        $this->assertEquals(
            'application/pdf',
            $doc->meta_content_type,
            'unexpected meta_content_type',
        );
    }

    public function testEnrichInternal(): void
    {
        $doc = $this->enrichWithData([]);

        $this->assertEquals(
            ['internal'],
            $doc->sp_source,
            'unexpected sp_source',
        );
    }

    public function testEnrichContent(): void
    {
        $doc = $this->enrichWithData([
            'metadata' => [
                'categories' => [
                    ['id' => 1, 'name' => 'CategoryA'],
                    ['id' => 2, 'name' => 'CategoryB'],
                ],
            ],
            'searchindexdata' => ['content' => 'abc'],
        ]);

        $this->assertEquals(
            'abc collected content CategoryA CategoryB',
            $doc->content,
            'unexpected content',
        );
    }

    public function testEnrichContactPointContent(): void
    {
        $doc = $this->enrichWithData([
            'metadata' => [
                'contactPoint' => [
                    'contactData' => [
                        'phoneList' => [
                            ['phone' => [
                                'countryCode' => '49',
                                'areaCode' => '251',
                                'localNumber' => '123',
                            ]],
                            ['phone' => [
                                'countryCode' => '49',
                                'areaCode' => '2571',
                                'localNumber' => '456',
                            ]],
                        ],
                        'emailList' => [
                            ['email' => 'test1@sitepark.com'],
                            ['email' => 'test2@sitepark.com'],
                        ],
                    ],
                    'addressData' => [
                        'street' => 'Neubrückenstr',
                        'buildingName' => 'Pressehaus',
                        'postOfficeBoxData' => [
                            'buildingName' => 'Sitepark',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertEquals(
            'collected content +49 251 0251 123 +49 2571 02571 456 ' .
            'test1@sitepark.com test2@sitepark.com ' .
            'Neubrückenstr Pressehaus Sitepark',
            $doc->content,
            'unexpected content',
        );
    }

    public function testEnrichStartletter(): void
    {
        $doc = $this->enrichWithData(['base' => [
            'startletter' => 'z',
        ]]);
        $this->assertEquals(
            'z',
            $doc->sp_startletter,
            'unexpected base.startletter',
        );
    }

    private function enrichWithResource(
        Resource $resource,
    ): IndexSchema2xDocument {
        /** @var IndexSchema2xDocument $doc */
        $doc = $this->enricher->enrichDocument(
            $resource,
            new IndexSchema2xDocument(),
            'progress-id',
        );
        return $doc;
    }

    /**
     * @param array<string, array<string,mixed>> $data
     */
    private function enrichWithData(
        array $data,
    ): IndexSchema2xDocument {
        $resource = $this->createResource($data);
        /** @var IndexSchema2xDocument $doc */
        $doc = $this->enricher->enrichDocument(
            $resource,
            new IndexSchema2xDocument(),
            'progress-id',
        );
        return $doc;
    }

    /**
     * @param array<string, array<string,mixed>> $data
     */
    private function createResource(array $data): Resource
    {
        return new Resource(
            $data['url'] ?? '',
            $data['id'] ?? '123',
            $data['name'] ?? '',
            $data['objectType'] ?? '',
            ResourceLanguage::of($data['locale'] ?? ''),
            new DataBag($data),
        );
    }
}
