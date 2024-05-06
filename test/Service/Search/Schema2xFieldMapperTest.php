<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Search\Dto\Search\Query\Facet\AbsoluteDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\CategoryFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ContentSectionTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\Facet;
use Atoolo\Search\Dto\Search\Query\Facet\GroupFacet;
use Atoolo\Search\Dto\Search\Query\Facet\ObjectTypeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\RelativeDateRangeFacet;
use Atoolo\Search\Dto\Search\Query\Facet\SiteFacet;
use Atoolo\Search\Dto\Search\Query\Filter\AbsoluteDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\CategoryFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ContentSectionTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\Filter;
use Atoolo\Search\Dto\Search\Query\Filter\GroupFilter;
use Atoolo\Search\Dto\Search\Query\Filter\ObjectTypeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\RelativeDateRangeFilter;
use Atoolo\Search\Dto\Search\Query\Filter\SiteFilter;
use Atoolo\Search\Dto\Search\Query\Sort\Criteria;
use Atoolo\Search\Dto\Search\Query\Sort\Date;
use Atoolo\Search\Dto\Search\Query\Sort\Headline;
use Atoolo\Search\Dto\Search\Query\Sort\Name;
use Atoolo\Search\Dto\Search\Query\Sort\Natural;
use Atoolo\Search\Dto\Search\Query\Sort\Score;
use Atoolo\Search\Service\Search\Schema2xFieldMapper;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Schema2xFieldMapper::class)]
class Schema2xFieldMapperTest extends TestCase
{
    private Schema2xFieldMapper $mapper;

    public function setUp(): void
    {
        $this->mapper = new Schema2xFieldMapper();
    }

    /**
     * @return array<array{class-string, string}>
     */
    public static function getFacets(): array
    {
        return [
            [ CategoryFacet::class, 'sp_category_path' ],
            [ ContentSectionTypeFacet::class, 'sp_contenttype' ],
            [ GroupFacet::class, 'sp_group_path' ],
            [ ObjectTypeFacet::class, 'sp_objecttype' ],
            [ SiteFacet::class, 'sp_site' ],
            [ RelativeDateRangeFacet::class, 'sp_date_list' ],
            [ AbsoluteDateRangeFacet::class, 'sp_date_list' ],
        ];
    }

    /**
     * @return array<array{class-string, string}>
     */
    public static function getFilter(): array
    {
        return [
            [ CategoryFilter::class, 'sp_category_path' ],
            [ ContentSectionTypeFilter::class, 'sp_contenttype' ],
            [ GroupFilter::class, 'sp_group_path' ],
            [ ObjectTypeFilter::class, 'sp_objecttype' ],
            [ SiteFilter::class, 'sp_site' ],
            [ RelativeDateRangeFilter::class, 'sp_date_list' ],
            [ AbsoluteDateRangeFilter::class, 'sp_date_list' ],
        ];
    }

    /**
     * @return array<array{class-string, string}>
     */
    public static function getSortCriteria(): array
    {
        return [
            [ Name::class, 'sp_name' ],
            [ Headline::class, 'sp_title' ],
            [ Date::class, 'sp_date' ],
            [ Natural::class, 'sp_sortvalue' ],
            [ Score::class, 'score' ],
        ];
    }

    /**
     * @param class-string $facetClass
     * @throws Exception
     */
    #[DataProvider('getFacets')]
    public function testGetFacetField(
        string $facetClass,
        string $expected
    ): void {
        /** @var Facet $facet */
        $facet = $this->createStub($facetClass);
        $this->assertEquals(
            $expected,
            $this->mapper->getFacetField($facet)
        );
    }

    public function testUnsupportedFacetField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mapper->getFacetField(
            $this->createStub(Facet::class)
        );
    }

    /**
     * @param class-string $filterClass
     * @throws Exception
     */
    #[DataProvider('getFilter')]
    public function testGetFilterField(
        string $filterClass,
        string $expected
    ): void {
        /** @var Filter $filter */
        $filter = $this->createStub($filterClass);
        $this->assertEquals(
            $expected,
            $this->mapper->getFilterField($filter)
        );
    }

    public function testUnsupportedFilterField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mapper->getFilterField(
            $this->createStub(Filter::class)
        );
    }

    /**
     * @param class-string $sortCriteriaClass
     * @throws Exception
     */
    #[DataProvider('getSortCriteria')]
    public function testGetSortField(
        string $sortCriteriaClass,
        string $expected
    ): void {
        /** @var Criteria $criteria */
        $criteria = $this->createStub($sortCriteriaClass);
        $this->assertEquals(
            $expected,
            $this->mapper->getSortField($criteria)
        );
    }

    public function testUnsupportedSortField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->mapper->getSortField(
            $this->createStub(Criteria::class)
        );
    }
}
