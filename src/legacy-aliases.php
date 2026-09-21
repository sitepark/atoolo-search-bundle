<?php

declare(strict_types=1);

/**
 * Registers the deprecated names of the indexer core, which moved to
 * atoolo/index-bundle with atoolo/search-bundle 1.18. Removed in 2.0.
 *
 * The aliases have to exist **before** any consumer code runs, not only when
 * a deprecated name is autoloaded: PHP does not autoload for parameter and
 * return type checks, so a host method that type hints a deprecated name
 * would reject an object of the new class while the alias file is still
 * unread. This file is therefore loaded through composer's `files`
 * autoloading.
 *
 * The deprecated names carry an `@deprecated` annotation at their old PSR-4
 * path, so that IDEs and static analysis still report them. Service ids,
 * tags, parameters and commands keep their runtime deprecation, because
 * those are resolved by name and can be reported precisely.
 *
 * The file runs while composer bootstraps the autoloader, before any
 * coverage collection starts, so its lines can never show up as covered.
 */

// @codeCoverageIgnoreStart
$atooloSearchLegacyAliases = [
    'Atoolo\\Search\\Console\\Application'
        => 'Atoolo\\Index\\Console\\Application',
    'Atoolo\\Search\\Console\\Command\\Io\\IndexerProgressBar'
        => 'Atoolo\\Index\\Console\\Command\\Io\\IndexerProgressBar',
    'Atoolo\\Search\\Console\\Command\\Io\\TypifiedInput'
        => 'Atoolo\\Index\\Console\\Command\\Io\\TypifiedInput',
    'Atoolo\\Search\\Dto\\Indexer\\IndexerConfiguration'
        => 'Atoolo\\Index\\Dto\\Indexer\\IndexerConfiguration',
    'Atoolo\\Search\\Dto\\Indexer\\IndexerParameter'
        => 'Atoolo\\Index\\Dto\\Indexer\\IndexerParameter',
    'Atoolo\\Search\\Dto\\Indexer\\IndexerStatus'
        => 'Atoolo\\Index\\Dto\\Indexer\\IndexerStatus',
    'Atoolo\\Search\\Dto\\Indexer\\IndexerStatusState'
        => 'Atoolo\\Index\\Dto\\Indexer\\IndexerStatusState',
    'Atoolo\\Search\\Exception\\DocumentEnrichingException'
        => 'Atoolo\\Index\\Exception\\DocumentEnrichingException',
    'Atoolo\\Search\\Exception\\UnsupportedIndexLanguageException'
        => 'Atoolo\\Index\\Exception\\UnsupportedIndexLanguageException',
    'Atoolo\\Search\\Indexer'
        => 'Atoolo\\Index\\Indexer',
    'Atoolo\\Search\\Service\\AbstractIndexer'
        => 'Atoolo\\Index\\Service\\AbstractIndexer',
    'Atoolo\\Search\\Service\\IndexName'
        => 'Atoolo\\Index\\Service\\IndexName',
    'Atoolo\\Search\\Service\\Indexer\\ContentCollector'
        => 'Atoolo\\Index\\Service\\Indexer\\ContentCollector',
    'Atoolo\\Search\\Service\\Indexer\\DocumentEnricher'
        => 'Atoolo\\Index\\Service\\Indexer\\DocumentEnricher',
    'Atoolo\\Search\\Service\\Indexer\\IndexDocument'
        => 'Atoolo\\Index\\Service\\Indexer\\IndexDocument',
    'Atoolo\\Search\\Service\\Indexer\\IndexDocumentDumper'
        => 'Atoolo\\Index\\Service\\Indexer\\IndexDocumentDumper',
    'Atoolo\\Search\\Service\\Indexer\\IndexerCollection'
        => 'Atoolo\\Index\\Service\\Indexer\\IndexerCollection',
    'Atoolo\\Search\\Service\\Indexer\\IndexerConfigurationLoader'
        => 'Atoolo\\Index\\Service\\Indexer\\IndexerConfigurationLoader',
    'Atoolo\\Search\\Service\\Indexer\\IndexerProgressHandler'
        => 'Atoolo\\Index\\Service\\Indexer\\IndexerProgressHandler',
    'Atoolo\\Search\\Service\\Indexer\\IndexerProgressState'
        => 'Atoolo\\Index\\Service\\Indexer\\IndexerProgressState',
    'Atoolo\\Search\\Service\\Indexer\\IndexerStatusStore'
        => 'Atoolo\\Index\\Service\\Indexer\\IndexerStatusStore',
    'Atoolo\\Search\\Service\\Indexer\\IndexingAborter'
        => 'Atoolo\\Index\\Service\\Indexer\\IndexingAborter',
    'Atoolo\\Search\\Service\\Indexer\\InternalResourceIndexer'
        => 'Atoolo\\Index\\Service\\Indexer\\InternalResourceIndexer',
    'Atoolo\\Search\\Service\\Indexer\\LocationFinder'
        => 'Atoolo\\Index\\Service\\Indexer\\LocationFinder',
    'Atoolo\\Search\\Service\\Indexer\\PhpLimitIncreaser'
        => 'Atoolo\\Index\\Service\\Indexer\\PhpLimitIncreaser',
    'Atoolo\\Search\\Service\\Indexer\\ResourceFilter'
        => 'Atoolo\\Index\\Service\\Indexer\\ResourceFilter',
    'Atoolo\\Search\\Service\\Indexer\\SiteKit\\ContentMatcher'
        => 'Atoolo\\Index\\Service\\Indexer\\SiteKit\\ContentMatcher',
    'Atoolo\\Search\\Service\\Indexer\\SiteKit\\HeadlineMatcher'
        => 'Atoolo\\Index\\Service\\Indexer\\SiteKit\\HeadlineMatcher',
    'Atoolo\\Search\\Service\\Indexer\\SiteKit\\LinkTextMatcher'
        => 'Atoolo\\Index\\Service\\Indexer\\SiteKit\\LinkTextMatcher',
    'Atoolo\\Search\\Service\\Indexer\\SiteKit\\NoIndexFilter'
        => 'Atoolo\\Index\\Service\\Indexer\\SiteKit\\NoIndexFilter',
    'Atoolo\\Search\\Service\\Indexer\\SiteKit\\QuoteSectionMatcher'
        => 'Atoolo\\Index\\Service\\Indexer\\SiteKit\\QuoteSectionMatcher',
    'Atoolo\\Search\\Service\\Indexer\\SiteKit\\RichtTextMatcher'
        => 'Atoolo\\Index\\Service\\Indexer\\SiteKit\\RichtTextMatcher',
    'Atoolo\\Search\\Service\\ResourceChannelBasedIndexName'
        => 'Atoolo\\Index\\Service\\ResourceChannelBasedIndexName',
];

foreach ($atooloSearchLegacyAliases as $atooloSearchOld => $atooloSearchNew) {
    if (
        class_exists($atooloSearchOld, false)
        || interface_exists($atooloSearchOld, false)
    ) {
        continue;
    }
    class_alias($atooloSearchNew, $atooloSearchOld);
}

unset($atooloSearchLegacyAliases, $atooloSearchOld, $atooloSearchNew);
// @codeCoverageIgnoreEnd
