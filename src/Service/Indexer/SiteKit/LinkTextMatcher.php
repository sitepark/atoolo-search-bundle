<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;

/**
 * Add Linktext of external Links
 */
class LinkTextMatcher implements ContentMatcher
{
    /**
     * @inheritDoc
     */
    public function match(array $path, array $value, Resource $resource): string|false
    {
        $modelType = $value['modelType'] ?? false;
        $linkType = $value['external'] ?? false;
        if ($modelType !== 'content.link.link' || $linkType === false) {
            return false;
        }

        $text = $value['label'] ?? false;
        return is_string($text) ? strip_tags($text) : false;
    }
}
