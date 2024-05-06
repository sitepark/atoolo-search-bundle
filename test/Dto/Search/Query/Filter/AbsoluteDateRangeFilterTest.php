<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Dto\Search\Query\Filter;

use Atoolo\Search\Dto\Search\Query\Filter\AbsoluteDateRangeFilter;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbsoluteDateRangeFilter::class)]
class AbsoluteDateRangeFilterTest extends TestCase
{
    public function testConstructorWithoutFromAndTo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AbsoluteDateRangeFilter(null, null);
    }
}
