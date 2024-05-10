<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Dto\Search\Query\Filter;

use Atoolo\Search\Dto\Search\Query\Filter\FieldFilter;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FieldFilter::class)]
class FieldFilterTest extends TestCase
{
    public function testConstructor(): void
    {
        $filter = new FieldFilter(['a']);
        $this->assertEquals(['a'], $filter->values, 'Unexpected values');
    }

    public function testConstructorWithEmptyValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FieldFilter([]);
    }
}
