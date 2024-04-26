<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Dto\Search\Query\Filter;

use Atoolo\Search\Dto\Search\Query\Filter\AbsoluteDateRangeFilter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbsoluteDateRangeFilter::class)]
class AbsoluteDateRangeFilterTest extends TestCase
{
    public function testGetQueryWithFromAndTo(): void
    {
        $from = new \DateTime('2021-01-01 00:00:00');
        $to = new \DateTime('2021-01-02 00:00:00');
        $filter = new AbsoluteDateRangeFilter($from, $to, 'sp_date_list');
        $this->assertEquals(
            'sp_date_list:[2021-01-01T00:00:00Z TO 2021-01-02T00:00:00Z]',
            $filter->getQuery()
        );
    }

    public function testGetQueryWithFrom(): void
    {
        $from = new \DateTime('2021-01-01 00:00:00');
        $filter = new AbsoluteDateRangeFilter($from, null, 'sp_date_list');
        $this->assertEquals(
            'sp_date_list:[2021-01-01T00:00:00Z TO *]',
            $filter->getQuery()
        );
    }

    public function testGetQueryWithTo(): void
    {
        $to = new \DateTime('2021-01-02 00:00:00');
        $filter = new AbsoluteDateRangeFilter(null, $to, 'sp_date_list');
        $this->assertEquals(
            'sp_date_list:[* TO 2021-01-02T00:00:00Z]',
            $filter->getQuery()
        );
    }
}
