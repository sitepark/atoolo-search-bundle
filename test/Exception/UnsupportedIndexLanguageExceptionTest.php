<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Exception;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Exception\UnsupportedIndexLanguageException;
use PHPUnit\Framework\TestCase;

class UnsupportedIndexLanguageExceptionTest extends TestCase
{
    public function testGetIndex(): void
    {
        $e = new UnsupportedIndexLanguageException(
            'test',
            ResourceLanguage::of('de'),
        );
        $this->assertEquals(
            'test',
            $e->getIndex(),
            'unexpected index',
        );
    }

    public function testGetLang(): void
    {
        $e = new UnsupportedIndexLanguageException(
            'test',
            ResourceLanguage::of('de'),
        );
        $this->assertEquals(
            ResourceLanguage::of('de'),
            $e->getLang(),
            'unexpected index',
        );
    }
}
