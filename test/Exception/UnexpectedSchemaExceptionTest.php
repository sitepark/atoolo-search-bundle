<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Exception;

use Atoolo\Search\Exception\UnexpectedSchemaException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UnexpectedSchemaException::class)]
class UnexpectedSchemaExceptionTest extends TestCase
{
    public function testConstruct(): void
    {
        $e = new UnexpectedSchemaException('schema 2x does not support nested documents');
        $this->assertEquals(
            'schema 2x does not support nested documents',
            $e->getMessage(),
        );
    }
}
