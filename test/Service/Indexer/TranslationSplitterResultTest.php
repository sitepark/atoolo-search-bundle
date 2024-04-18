<?php

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Resource\ResourceLocation;
use Atoolo\Search\Service\Indexer\TranslationSplitterResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TranslationSplitterResult::class)]
class TranslationSplitterResultTest extends TestCase
{
    public function testGetBases(): void
    {
        $result = new TranslationSplitterResult(
            [
                ResourceLocation::of('/a/b.php')
            ],
            []
        );
        $this->assertEquals(
            [
                ResourceLocation::of('/a/b.php')
            ],
            $result->getBases(),
            'unexpected bases'
        );
    }

    public function testLanguages(): void
    {
        $it = ResourceLanguage::of('it_IT');
        $en = ResourceLanguage::of('en_US');

        $result = new TranslationSplitterResult(
            [],
            [
                $it->code => [ResourceLocation::of('/a/b.php', $it)],
                $en->code => [ResourceLocation::of('/a/b.php', $en)],
            ]
        );

        $this->assertEquals(
            [$en, $it],
            $result->getLanguages(),
            'unexpected languages'
        );
    }

    public function testGetTranslations(): void
    {
        $it = ResourceLanguage::of('it_IT');
        $en = ResourceLanguage::of('en_US');
        $result = new TranslationSplitterResult(
            [],
            [
                $it->code => [
                    ResourceLocation::of('/a/b.php', $it),
                    ResourceLocation::of('/c/d.php', $it),
                ],
                $en->code => [
                    ResourceLocation::of('/a/b.php', $en),
                    ResourceLocation::of('/a/b.php', $en),
                ]
            ]
        );

        $translations = $result->getTranslations($it);

        $expected = [
            ResourceLocation::of('/a/b.php', $it),
            ResourceLocation::of('/c/d.php', $it),
        ];

        $this->assertEquals(
            $expected,
            $translations,
            'unexpected translations'
        );
    }
    public function testGetMissingTranslations(): void
    {
        $result = new TranslationSplitterResult([], []);
        $lang = ResourceLanguage::of('en_US');

        $this->assertEquals(
            [],
            $result->getTranslations($lang),
            'empty array expected'
        );
    }
}
