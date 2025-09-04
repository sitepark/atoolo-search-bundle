<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Serializer\Search\Query;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Boosting;
use Atoolo\Search\Dto\Search\Query\DateIntervalDirection;
use Atoolo\Search\Dto\Search\Query\DateRangeRound;
use Atoolo\Search\Dto\Search\Query\DirectedDateInterval;
use Atoolo\Search\Dto\Search\Query\Facet\AbsoluteDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\CategoryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ContentSectionTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ContentTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\GroupFacet;
use Atoolo\Search\Dto\Search\Query\Facet\MultiQueryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ObjectTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\QueryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\RelativeDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\SiteFacet;
use Atoolo\Search\Dto\Search\Query\Facet\SourceFacet;
use Atoolo\Search\Dto\Search\Query\Facet\SpatialDistanceRangeFacet;
use Atoolo\Search\Dto\Search\Query\Filter\AbsoluteDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\AndFilter;
use Atoolo\Search\Dto\Search\Query\Filter\CategoryFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ContentSectionTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ContentTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\GeoLocatedFilter;
use Atoolo\Search\Dto\Search\Query\Filter\GroupFilter;
use Atoolo\Search\Dto\Search\Query\Filter\IdFilter;
use Atoolo\Search\Dto\Search\Query\Filter\NotFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ObjectTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\OrFilter;
use Atoolo\Search\Dto\Search\Query\Filter\QueryFilter;
use Atoolo\Search\Dto\Search\Query\Filter\RelativeDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SiteFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SourceFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialArbitraryRectangleFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialOrbitalFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SpatialOrbitalMode;
use Atoolo\Search\Dto\Search\Query\GeoPoint;
use Atoolo\Search\Dto\Search\Query\QueryOperator;
use Atoolo\Search\Dto\Search\Query\SearchQuery;
use Atoolo\Search\Dto\Search\Query\SearchQueryBuilder;
use Atoolo\Search\Dto\Search\Query\Sort\CustomField;
use Atoolo\Search\Dto\Search\Query\Sort\Date;
use Atoolo\Search\Dto\Search\Query\Sort\Direction;
use Atoolo\Search\Dto\Search\Query\Sort\Name;
use Atoolo\Search\Dto\Search\Query\Sort\Natural;
use Atoolo\Search\Dto\Search\Query\Sort\Score;
use Atoolo\Search\Dto\Search\Query\Sort\SpatialDist;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Atoolo\Search\Serializer\Search\Query\SearchQueryDenormalizer;
use DateInterval;
use Symfony\Component\PropertyInfo\Extractor\PhpStanExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateIntervalNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

#[CoversClass(SearchQueryDenormalizer::class)]
class SearchQueryDenormalizerTest extends TestCase
{
    private Serializer $serializer;

    protected function setUp(): void
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [
            new BackedEnumNormalizer(),
            new ArrayDenormalizer(),
            new SearchQueryDenormalizer(),
            new DateTimeNormalizer(),
            new DateIntervalNormalizer(),
            new ObjectNormalizer(
                new ClassMetadataFactory(new AttributeLoader()),
                null,
                null,
                new PhpStanExtractor(),
            ),
        ];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    public function testDenormalize(): void
    {
        $data =  [
            'text' => 'TEXT',
            'archive' => true,
            'offset' => 100,
            'limit' => 200,
            'sort' => [
                [
                    'type' => 'customField',
                    'field' => 'sp_custom_field',
                    'direction' => 'DESC',
                ],
                [
                    'type' => 'date',
                    'direction' => 'ASC',
                ],
                [
                    'type' => 'name',
                    'direction' => 'ASC',
                ],
                [
                    'type' => 'natural',
                    'direction' => 'DESC',
                ],
                [
                    'type' => 'score',
                    'direction' => 'DESC',
                ],
                [
                    'type' => 'spatialDist',
                    'point' => [
                        'lng' => 1.1,
                        'lat' => 1.2,
                    ],
                    'direction' => 'ASC',
                ],
            ],
            'boosting' => [
                'boostFunctions' => ['a', 'b', 'c'],
                'boostQueries' => ['d', 'e', 'f'],
                'phraseFields' => ['g', 'h', 'i'],
                'queryFields' => ['j', 'k', 'l'],
                'tie' => 1337.0,
            ],
            'lang' => 'DE',
            'timeZone' => 'Europe/Berlin',
            'defaultQueryOperator' => 'AND',
            'distanceReferencePoint' => [
                'lng' => 0.2,
                'lat' => 0.5,
            ],
            'facets' => [
                [
                    'type' => 'absoluteDateRange',
                    'key' => 'absoluteDateRange',
                    'from' => '10.02.2025',
                    'to' => '11.02.2025',
                    'gap' => 'P1D',
                ],
                [
                    'type' => 'category',
                    'key' => 'categories',
                    'terms' => ['category1', 'category2'],
                ],
                [
                    'type' => 'contentSectionType',
                    'key' => 'contentSectionType',
                    'terms' => ['contentSectionType1', 'contentSectionType2'],
                ],
                [
                    'type' => 'contentType',
                    'key' => 'contentType',
                    'terms' => ['contentType1', 'contentType2'],
                ],
                [
                    'type' => 'group',
                    'key' => 'group',
                    'terms' => ['group1', 'group2'],
                ],
                [
                    'type' => 'multiQuery',
                    'key' => 'multiQuery',
                    'queries' => [
                        [
                            'type' => 'queryFacet',
                            'key' => 'queryFacet1',
                            'query' => 'some:query1',
                        ],
                        [
                            'type' => 'queryFacet',
                            'key' => 'queryFacet2',
                            'query' => 'some:query2',
                        ],
                    ],
                ],
                [
                    'type' => 'objectType',
                    'key' => 'objectType',
                    'terms' => ['objectType1', 'objectType2'],
                ],
                [
                    'type' => 'relativeDateRange',
                    'key' => 'relativeDateRange-1',
                    'base' => '10.02.2025',
                    'before' => 'P2Y4DT6H8M',
                    'after' => 'P32D',
                    'gap' => 'P1D',
                    'roundStart' => 'START_OF_DAY',
                    'roundEnd' => 'END_OF_DAY',
                ],
                [
                    'type' => 'relativeDateRange',
                    'key' => 'relativeDateRange-2',
                    'base' => '10.02.2025',
                    'before' => [
                        'interval' => 'P2Y4DT6H8M',
                        'direction' => 'PAST',
                    ],
                    'after' => [
                        'interval' => 'P32D',
                        'direction' => 'FUTURE',
                    ],
                    'gap' => 'P1D',
                    'roundStart' => 'START_OF_DAY',
                    'roundEnd' => 'END_OF_DAY',
                ],
                [
                    'type' => 'site',
                    'key' => 'site',
                    'terms' => ['site1', 'site2'],
                ],
                [
                    'type' => 'source',
                    'key' => 'source',
                    'terms' => ['source1', 'source2'],
                ],
                [
                    'type' => 'spatialDistanceRange',
                    'key' => 'spatialDistanceRange',
                    'point' => [
                        'lng' => 2.0,
                        'lat' => 2.2,
                    ],
                    'from' => 4.0,
                    'to' => 8.9,
                ],

            ],
            'filter' => [
                [
                    'type' => 'group',
                    'values' => ['group1', 'group2'],
                ],
                [
                    'type' => 'and',
                    'filter' => [
                        [
                            'type' => 'site',
                            'values' => ['site1', 'site2'],
                        ],
                        [
                            'type' => 'contentSectionType',
                            'values' => ['contentSectionType1', 'contentSectionType2'],
                        ],
                    ],
                ],
                [
                    'type' => 'or',
                    'filter' => [
                        [
                            'type' => 'category',
                            'values' => ['category1', 'category2'],
                        ],
                        [
                            'type' => 'contentType',
                            'values' => ['contentType1', 'contentType2'],
                        ],
                    ],
                ],
                [
                    'type' => 'not',
                    'filter' => [
                        'type' => 'geoLocated',
                        'exists' => true,
                    ],
                ],
                [
                    'type' => 'absoluteDateRange',
                    'from' => '12.02.2025',
                    'to' => '13.02.2025',
                ],
                [
                    'type' => 'id',
                    'values' => ['id1', 'id2'],
                ],
                [
                    'type' => 'objectType',
                    'values' => ['objectType1', 'objectType2'],
                ],
                [
                    'type' => 'query',
                    'query' => 'some:query',
                ],
                [
                    'type' => 'relativeDateRange',
                    'base' => '10.02.2025',
                    'before' => 'P2Y4DT6H8M',
                    'after' => 'P32D',
                    'roundStart' => 'START_OF_DAY',
                    'roundEnd' => 'END_OF_DAY',
                ],
                [
                    'type' => 'relativeDateRange',
                    'base' => '10.02.2025',
                    'before' => [
                        'interval' => 'P2Y4DT6H8M',
                        'direction' => 'PAST',
                    ],
                    'after' => [
                        'interval' => 'P32D',
                        'direction' => 'FUTURE',
                    ],
                    'roundStart' => 'START_OF_DAY',
                    'roundEnd' => 'END_OF_DAY',
                ],
                [
                    'type' => 'source',
                    'values' => ['source1', 'source2'],
                ],
                [
                    'type' => 'spatialArbitraryRectangle',
                    'lowerLeftCorner' => [
                        'lng' => 1.1,
                        'lat' => 4.4,
                    ],
                    'upperRightCorner' => [
                        'lng' => 5.5,
                        'lat' => 2.0,
                    ],
                ],
                [
                    'type' => 'spatialOrbital',
                    'distance' => 3.3,
                    'centerPoint' => [
                        'lng' => 4.5,
                        'lat' => 6.0,
                    ],
                    'mode' => 'bounding-box',
                ],
            ],
            'explain' => false,
        ];
        /** @var ?SearchQuery $actual */
        $actual = $this->serializer->denormalize($data, SearchQuery::class);
        $expected = (new SearchQueryBuilder())
            ->text('TEXT')
            ->archive(true)
            ->offset(100)
            ->limit(200)
            ->sort(
                new CustomField('sp_custom_field', Direction::DESC),
                new Date(Direction::ASC),
                new Name(Direction::ASC),
                new Natural(Direction::DESC),
                new Score(Direction::DESC),
                new SpatialDist(Direction::ASC, new GeoPoint(1.1, 1.2)),
            )
            ->boosting(
                new Boosting(
                    boostFunctions: ['a', 'b', 'c'],
                    boostQueries: ['d', 'e', 'f'],
                    phraseFields: ['g', 'h', 'i'],
                    queryFields: ['j', 'k', 'l'],
                    tie: 1337.0,
                ),
            )
            ->lang(ResourceLanguage::of('DE'))
            ->timeZone(new \DateTimeZone('Europe/Berlin'))
            ->defaultQueryOperator(QueryOperator::AND)
            ->facet(
                new AbsoluteDateRangeFacet(
                    'absoluteDateRange',
                    new \DateTime('10.02.2025'),
                    new \DateTime('11.02.2025'),
                    new \DateInterval('P1D'),
                ),
                new CategoryFacet('categories', ['category1', 'category2']),
                new ContentSectionTypeFacet('contentSectionType', ['contentSectionType1', 'contentSectionType2']),
                new ContentTypeFacet('contentType', ['contentType1', 'contentType2']),
                new GroupFacet('group', ['group1', 'group2']),
                new MultiQueryFacet(
                    'multiQuery',
                    [
                        new QueryFacet('queryFacet1', 'some:query1'),
                        new QueryFacet('queryFacet2', 'some:query2'),
                    ],
                ),
                new ObjectTypeFacet('objectType', ['objectType1', 'objectType2']),
                new RelativeDateRangeFacet(
                    'relativeDateRange-1',
                    new \DateTime('10.02.2025'),
                    new \DateInterval('P2Y4DT6H8M'),
                    new \DateInterval('P32D'),
                    new \DateInterval('P1D'),
                    DateRangeRound::START_OF_DAY,
                    DateRangeRound::END_OF_DAY,
                ),
                new RelativeDateRangeFacet(
                    'relativeDateRange-2',
                    new \DateTime('10.02.2025'),
                    new DirectedDateInterval(new \DateInterval('P2Y4DT6H8M'), DateIntervalDirection::PAST),
                    new DirectedDateInterval(new \DateInterval('P32D'), DateIntervalDirection::FUTURE),
                    new \DateInterval('P1D'),
                    DateRangeRound::START_OF_DAY,
                    DateRangeRound::END_OF_DAY,
                ),
                new SiteFacet('site', ['site1', 'site2']),
                new SourceFacet('source', ['source1', 'source2']),
                new SpatialDistanceRangeFacet(
                    'spatialDistanceRange',
                    new GeoPoint(2.0, 2.2),
                    4.0,
                    8.9,
                ),
            )
            ->filter(
                new GroupFilter(['group1', 'group2']),
                new AndFilter([
                    new SiteFilter(['site1', 'site2']),
                    new ContentSectionTypeFilter(['contentSectionType1', 'contentSectionType2']),
                ]),
                new OrFilter([
                    new CategoryFilter(['category1', 'category2']),
                    new ContentTypeFilter(['contentType1', 'contentType2']),
                ]),
                new NotFilter(
                    new GeoLocatedFilter(true),
                ),
                new AbsoluteDateRangeFilter(
                    new \DateTime('12.02.2025'),
                    new \DateTime('13.02.2025'),
                ),
                new IdFilter(['id1', 'id2']),
                new ObjectTypeFilter(['objectType1', 'objectType2']),
                new QueryFilter('some:query'),
                new RelativeDateRangeFilter(
                    new \DateTime('10.02.2025'),
                    new \DateInterval('P2Y4DT6H8M'),
                    new \DateInterval('P32D'),
                    DateRangeRound::START_OF_DAY,
                    DateRangeRound::END_OF_DAY,
                ),
                new RelativeDateRangeFilter(
                    new \DateTime('10.02.2025'),
                    new DirectedDateInterval(new \DateInterval('P2Y4DT6H8M'), DateIntervalDirection::PAST),
                    new DirectedDateInterval(new \DateInterval('P32D'), DateIntervalDirection::FUTURE),
                    DateRangeRound::START_OF_DAY,
                    DateRangeRound::END_OF_DAY,
                ),
                new SourceFilter(['source1', 'source2']),
                new SpatialArbitraryRectangleFilter(
                    new GeoPoint(1.1, 4.4),
                    new GeoPoint(5.5, 2.0),
                ),
                new SpatialOrbitalFilter(
                    3.3,
                    new GeoPoint(4.5, 6.0),
                    SpatialOrbitalMode::BOUNDING_BOX,
                ),
            )
            ->distanceReferencePoint(new GeoPoint(0.2, 0.5))
            ->build();
        $this->assertEquals($expected, $actual);
    }

    public function testDenormalizeInvalid(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->serializer->denormalize('invalid data', SearchQuery::class);
    }
}
