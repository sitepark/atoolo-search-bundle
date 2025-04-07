<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Search\Service\Search\QueryTemplateResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QueryTemplateResolver::class)]
class QueryTemplateResolverTest extends TestCase
{
    public function testResolveWithValidVariables(): void
    {
        $resolver = new QueryTemplateResolver();
        $query = 'myfield:{myvar}';
        $variables = ['myvar' => 'myvalue'];

        $this->assertSame('myfield:myvalue', $resolver->resolve($query, $variables));
    }
}
