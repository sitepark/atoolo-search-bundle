<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use Atoolo\Search\Service\Indexer\Schema2xIndexDocumentFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Schema2xIndexDocumentFactory::class)]
class Schema2xIndexDocumentFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new Schema2xIndexDocumentFactory();

        $this->assertInstanceOf(
            IndexSchema2xDocument::class,
            $factory->create(),
            'unexpected document',
        );
    }
}
