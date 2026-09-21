<?php

declare(strict_types=1);

namespace Atoolo\Search\DependencyInjection\Compiler;

use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Keeps hosts working that still use the tags and parameters of the
 * search-bundle for indexers that now live in atoolo/index-bundle.
 *
 * Service ids are aliased in the yaml configuration; tags and parameters
 * cannot be aliased and are therefore copied here. Everything this pass
 * handles is removed in 2.0.
 *
 * @deprecated since atoolo/search-bundle 1.18, will be removed in 2.0
 */
class LegacyIndexerTagPass implements CompilerPassInterface
{
    private const TAGS = [
        'atoolo_search.indexer' => 'atoolo_index.indexer',
        'atoolo_search.indexer.sitekit.content_matcher'
            => 'atoolo_index.indexer.sitekit.content_matcher',
    ];

    private const PARAMETERS = [
        'atoolo_search.indexer.time_limit'
            => 'atoolo_index.indexer.time_limit',
        'atoolo_search.indexer.memory_limit'
            => 'atoolo_index.indexer.memory_limit',
    ];

    public function process(ContainerBuilder $container): void
    {
        $this->assertIndexBundleRegistered($container);
        $this->copyTags($container);
        $this->syncParameters($container);
    }

    private function assertIndexBundleRegistered(
        ContainerBuilder $container,
    ): void {
        if ($container->has('atoolo_index.indexer.indexer_collection')) {
            return;
        }
        throw new LogicException(
            'The service "atoolo_index.indexer.indexer_collection" is '
            . 'missing. The indexer of atoolo/search-bundle has moved to '
            . 'atoolo/index-bundle. Register '
            . 'Atoolo\Index\AtooloIndexBundle in config/bundles.php.',
        );
    }

    private function copyTags(ContainerBuilder $container): void
    {
        foreach (self::TAGS as $legacyTag => $tag) {
            $taggedServiceIds = $container->findTaggedServiceIds(
                $legacyTag,
                true,
            );
            foreach ($taggedServiceIds as $id => $attributesList) {
                $definition = $container->getDefinition($id);
                foreach ($attributesList as $attributes) {
                    if ($this->hasTag($definition, $tag, $attributes)) {
                        continue;
                    }
                    $definition->addTag($tag, $attributes);
                }
                trigger_deprecation(
                    'atoolo/search-bundle',
                    '1.18',
                    'The tag "%s" of service "%s" is deprecated, '
                    . 'use "%s" instead.',
                    $legacyTag,
                    $id,
                    $tag,
                );
            }
        }
    }

    /**
     * @param array<string,mixed> $attributes
     */
    private function hasTag(
        \Symfony\Component\DependencyInjection\Definition $definition,
        string $tag,
        array $attributes,
    ): bool {
        foreach ($definition->getTag($tag) as $existing) {
            if ($existing === $attributes) {
                return true;
            }
        }
        return false;
    }

    /**
     * Parameters cannot be aliased. A value the host set under the old name
     * wins and is copied over; otherwise the new value is mirrored back, so
     * that reads of the old name keep working.
     */
    private function syncParameters(ContainerBuilder $container): void
    {
        foreach (self::PARAMETERS as $legacyName => $name) {
            if ($container->hasParameter($legacyName)) {
                $container->setParameter(
                    $name,
                    $container->getParameter($legacyName),
                );
                trigger_deprecation(
                    'atoolo/search-bundle',
                    '1.18',
                    'The parameter "%s" is deprecated, use "%s" instead.',
                    $legacyName,
                    $name,
                );
                continue;
            }
            if ($container->hasParameter($name)) {
                $container->setParameter(
                    $legacyName,
                    $container->getParameter($name),
                );
            }
        }
    }
}
