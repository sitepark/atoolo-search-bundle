<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Search\Service\Indexer\IndexSchema2xDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexSchema2xDocument::class)]
class IndexSchema2xDocumentTest extends TestCase
{
    public function testGetFields(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->sp_id = '123';

        $this->assertEquals(
            ['sp_id' => '123'],
            $doc->getFields(),
            'unexpected fields',
        );
    }

    public function testSetMetaInt(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->setMetaInt('myname', 42);

        $this->assertEquals(
            ['sp_meta_int_myname' => 42],
            $doc->getFields(),
            'unexpected meta fields',
        );
    }

    public function testSetMetaSingleInt(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->setMetaSingleInt('myname', 42);

        $this->assertEquals(
            ['sp_meta_single_int_myname' => 42],
            $doc->getFields(),
            'unexpected meta fields',
        );
    }

    public function testSetMetaLong(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->setMetaLong('myname', 42);

        $this->assertEquals(
            ['sp_meta_long_myname' => 42],
            $doc->getFields(),
            'unexpected meta fields',
        );
    }

    public function testSetMetaSingleLong(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->setMetaSingleLong('myname', 42);

        $this->assertEquals(
            ['sp_meta_single_long_myname' => 42],
            $doc->getFields(),
            'unexpected meta fields',
        );
    }

    public function testSetMetaFloat(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->setMetaFloat('myname', 4.2);

        $this->assertEquals(
            ['sp_meta_float_myname' => 4.2],
            $doc->getFields(),
            'unexpected meta fields',
        );
    }

    public function testSetMetaSingleFloat(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->setMetaSingleFloat('myname', 4.2);

        $this->assertEquals(
            ['sp_meta_single_float_myname' => 4.2],
            $doc->getFields(),
            'unexpected meta fields',
        );
    }

    public function testSetMetaString(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->setMetaString('myname', 'myvalue');

        $this->assertEquals(
            ['sp_meta_string_myname' => 'myvalue'],
            $doc->getFields(),
            'unexpected meta fields',
        );
    }

    public function testSetMetaSingleString(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->setMetaSingleString('myname', 'myvalue');

        $this->assertEquals(
            ['sp_meta_single_string_myname' => 'myvalue'],
            $doc->getFields(),
            'unexpected meta fields',
        );
    }

    public function testSetMetaText(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->setMetaText('myname', 'myvalue');

        $this->assertEquals(
            ['sp_meta_text_myname' => 'myvalue'],
            $doc->getFields(),
            'unexpected meta fields',
        );
    }

    public function testSetMetaSingleText(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->setMetaSingleText('myname', 'myvalue');

        $this->assertEquals(
            ['sp_meta_single_text_myname' => 'myvalue'],
            $doc->getFields(),
            'unexpected meta fields',
        );
    }

    public function testSetMetaBool(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->setMetaBool('myname', true);

        $this->assertEquals(
            ['sp_meta_bool_myname' => true],
            $doc->getFields(),
            'unexpected meta fields',
        );
    }

    public function testSetMetaSingleBool(): void
    {
        $doc = new IndexSchema2xDocument();
        $doc->setMetaSingleBool('myname', true);

        $this->assertEquals(
            ['sp_meta_single_bool_myname' => true],
            $doc->getFields(),
            'unexpected meta fields',
        );
    }
}
