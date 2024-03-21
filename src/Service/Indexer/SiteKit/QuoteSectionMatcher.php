<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

/**
 *  @phpstan-type Model array{quote?: ?string, citation?: ?string}
 */
class QuoteSectionMatcher implements ContentMatcher
{
    /**
     * @inheritDoc
     */
    public function match(array $path, array $value): string|false
    {
        $len = count($path);
        if ($len < 1) {
            return false;
        }

        if (
            $path[$len - 1] !== 'items'
        ) {
            return false;
        }

        if (($value['type'] ?? '') !== 'quote') {
            return false;
        }

        $model = $value['model'] ?? false;
        if (!is_array($model)) {
            return false;
        }

        /** @var Model $model */

        $content = [];
        $quote = $model['quote'] ?? '';
        if (is_string($quote)) {
            $content[] = $quote;
        }
        $citation = $model['citation'] ?? '';
        if (is_string($citation)) {
            $content[] = $citation;
        }

        return implode(' ', $content);
    }
}
