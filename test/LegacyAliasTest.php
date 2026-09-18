<?php

declare(strict_types=1);

namespace Atoolo\Search\Test;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * The indexer core moved to atoolo/index-bundle. The old names stay
 * available as aliases until 2.0.
 *
 * Note the limit of a `class_alias` based layer: PHP does not autoload for
 * parameter and return type checks. A type hint on a deprecated name only
 * matches an object of the new class once that deprecated name has been
 * loaded - by a `use` plus `new`, an `instanceof`, an `extends` or an
 * `implements` somewhere. Host code that only type hints the old name has
 * to import the new one.
 */
class LegacyAliasTest extends TestCase
{
    /**
     * @return array<string,array{string,string,string}>
     */
    public static function aliasProvider(): array
    {
        $pairs = [
            ['Indexer', 'interface'],
            ['Console\Application', 'class'],
            ['Console\Command\Io\IndexerProgressBar', 'class'],
            ['Console\Command\Io\TypifiedInput', 'class'],
            ['Dto\Indexer\IndexerConfiguration', 'class'],
            ['Dto\Indexer\IndexerParameter', 'class'],
            ['Dto\Indexer\IndexerStatus', 'class'],
            ['Dto\Indexer\IndexerStatusState', 'enum'],
            ['Dto\Indexer\InternalResourceIndexerEvent', 'class'],
            ['Exception\DocumentEnrichingException', 'class'],
            ['Exception\UnsupportedIndexLanguageException', 'class'],
            ['Service\AbstractIndexer', 'class'],
            ['Service\IndexName', 'interface'],
            ['Service\ResourceChannelBasedIndexName', 'class'],
            ['Service\Indexer\ContentCollector', 'class'],
            ['Service\Indexer\DocumentEnricher', 'interface'],
            ['Service\Indexer\IndexDocument', 'interface'],
            ['Service\Indexer\IndexDocumentDumper', 'class'],
            ['Service\Indexer\IndexerCollection', 'class'],
            ['Service\Indexer\IndexerConfigurationLoader', 'class'],
            ['Service\Indexer\IndexerProgressHandler', 'interface'],
            ['Service\Indexer\IndexerProgressState', 'class'],
            ['Service\Indexer\IndexerStatusStore', 'class'],
            ['Service\Indexer\IndexingAborter', 'class'],
            ['Service\Indexer\InternalResourceIndexer', 'class'],
            ['Service\Indexer\LocationFinder', 'class'],
            ['Service\Indexer\PhpLimitIncreaser', 'class'],
            ['Service\Indexer\ResourceFilter', 'interface'],
            ['Service\Indexer\SiteKit\ContentMatcher', 'interface'],
            ['Service\Indexer\SiteKit\HeadlineMatcher', 'class'],
            ['Service\Indexer\SiteKit\LinkTextMatcher', 'class'],
            ['Service\Indexer\SiteKit\NoIndexFilter', 'class'],
            ['Service\Indexer\SiteKit\QuoteSectionMatcher', 'class'],
            ['Service\Indexer\SiteKit\RichtTextMatcher', 'class'],
        ];

        $cases = [];
        foreach ($pairs as [$name, $kind]) {
            $cases[$name] = [
                'Atoolo\Search\\' . $name,
                'Atoolo\Index\\' . $name,
                $kind,
            ];
        }
        return $cases;
    }

    #[DataProvider('aliasProvider')]
    public function testAlias(string $old, string $new, string $kind): void
    {
        $exists = match ($kind) {
            'interface' => interface_exists($old),
            'enum' => enum_exists($old),
            default => class_exists($old),
        };
        $this->assertTrue($exists, $old . ' should still exist');

        $this->assertEquals(
            $new,
            (new ReflectionClass($old))->getName(),
            $old . ' should be an alias of ' . $new,
        );

        $this->assertTrue(
            is_a($new, $old, true),
            $new . ' should be usable where ' . $old . ' is expected',
        );
    }
}
