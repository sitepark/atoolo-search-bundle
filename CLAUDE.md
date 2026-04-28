# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Atoolo Search Bundle** is a Symfony bundle providing Apache Solr-based indexing and searching capabilities for the Atoolo resource bundle. It exposes three main interfaces: `Indexer`, `Search`, and `Suggest`.

## Common Commands

```bash
# Install dependencies
composer install

# Auto-fix code style
composer cs-fix

# Run all static analysis (phplint, phpstan level 9, php-cs-fixer, compatibility)
composer analyse

# Run all tests with coverage
composer test

# Run only PHPUnit tests
composer test:phpunit

# Run mutation tests
composer test:infection

# Generate reports
composer report
```

To run a single test class:
```bash
./vendor/bin/phpunit test/Service/Search/InternalResourceFactoryTest.php
```

## Architecture

The bundle follows a layered architecture with three main functional areas:

### Core Interfaces
- `Indexer` — indexes resources into Solr
- `Search` — executes search queries, returns `SearchResult`
- `Suggest` — provides auto-complete suggestions
- `MoreLikeThis` — finds similar documents

### Indexing Pipeline (`src/Service/Indexer/`)
`InternalResourceIndexer` is the primary indexer. It:
1. Discovers resources via `LocationFinder`
2. Filters them through `IndexerFilter` (composite of `NoIndexFilter`, `NoNavigationFilter`)
3. Enriches documents via tagged `DocumentEnricher` implementations (e.g. `DefaultSchema2xDocumentEnricher`)
4. Sends batched updates to Solr via `SolrIndexService` / `SolrIndexUpdater`

Content matchers (tagged `atoolo_search.indexer.sitekit.content_matcher`) extract text from structured SiteKit data.

Indexing runs automatically via `InternalResourceIndexerScheduler` (cron: `0 2 * * *`).

### Search Pipeline (`src/Service/Search/`)
`SolrSearch` receives a `SearchQuery`, applies `SolrQueryModifier` implementations (filter/facet appenders), sends to Solr, then resolves hits to `Resource` objects via `SolrResultToResourceResolver`.

`SolrResultToResourceResolver` iterates over tagged `ResourceFactory` implementations (priority order):
- `InternalIdBasedResourceFactory` (priority 12) — loads by internal ID
- `InternalMediaResourceFactory` (priority 11) — loads media resources
- `ExternalResourceFactory` (priority 11) — handles external URLs
- `InternalResourceFactory` (priority 10) — default internal resource loading

### Configuration
- `config/indexer.yaml` — indexer services, filters, enrichers, scheduler config
- `config/search.yaml` — search/suggest services, Solr connection (env vars: `SOLR_SCHEME`, `SOLR_HOST`, `SOLR_PORT`, `SOLR_PATH`, `SOLR_PROXY`, `SOLR_TIMEOUT`), resource factories

## Code Quality Standards

- **PHPStan level 9** — all code must pass strict static analysis
- **PHP-CS-Fixer** — enforced formatting; run `composer cs-fix` before committing
- **PHP 8.1–8.4** compatibility required; tested in CI on 8.2, 8.3, 8.4
- Coverage reports go to `var/log/clover/`; JUnit reports to `var/log/surefire-reports/`
