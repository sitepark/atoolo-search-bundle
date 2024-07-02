<?php

namespace Atoolo\Search\Test\Service\Indexer\SiteKit;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Service\Indexer\SiteKit\SubDirTranslationSplitter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SubDirTranslationSplitter::class)]
class SubDirTranslationSplitterTest extends TestCase
{
    public function testBases(): void
    {
        $splitter = new SubDirTranslationSplitter();
        $result = $splitter->split(['/a/b.php']);

        $this->assertEquals(
            ['/a/b.php'],
            $result->getBases(),
            'unexpected bases',
        );
    }

    public function testLanguages(): void
    {
        $splitter = new SubDirTranslationSplitter();
        $result = $splitter->split([
            '/a/b.php',
            '/a/b.php.translations/it_IT.php',
            '/a/b.php.translations/en_US.php',
        ]);

        $this->assertEquals(
            [
                ResourceLanguage::of('en_US'),
                ResourceLanguage::of('it_IT'),
            ],
            $result->getLanguages(),
            'unexpected locales',
        );
    }

    public function testGetTranlsations(): void
    {
        $splitter = new SubDirTranslationSplitter();
        $result = $splitter->split([
            '/a/b.php',
            '/a/b.php.translations/it_IT.php',
            '/a/b.php.translations/en_US.php',
            '/c/d.php',
            '/c/d.php.translations/it_IT.php',
            '/c/d.php.translations/en_US.php',
        ]);

        $lang = ResourceLanguage::of('it_IT');
        $translations = $result->getTranslations($lang);

        $expected = [
            '/a/b.php',
            '/c/d.php',
        ];

        $this->assertEquals(
            $expected,
            $translations,
            'unexpected translations',
        );
    }

    public function testSplitWithLocParameter(): void
    {
        $splitter = new SubDirTranslationSplitter();
        $result = $splitter->split([
            '/a/b.php?loc=en_US',
        ]);

        $lang = ResourceLanguage::of('en_US');
        $translations = $result->getTranslations($lang);

        $expected = [
            '/a/b.php',
        ];

        $this->assertEquals(
            $expected,
            $translations,
            'unexpected translations',
        );
    }

    public function testSplitWithOutPath(): void
    {
        $splitter = new SubDirTranslationSplitter();
        $result = $splitter->split([
            '?a=b',
        ]);

        $this->assertEquals(
            [],
            $result->getBases(),
            'bases should be empty',
        );
    }

    public function testSplitWithUnsupportedParameter(): void
    {
        $splitter = new SubDirTranslationSplitter();
        $result = $splitter->split([
            '/test.php?a=b',
        ]);

        $this->assertEquals(
            ['/test.php'],
            $result->getBases(),
            'unexpected bases',
        );
    }
}
