<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;

class RichtTextMatcher implements ContentMatcher
{
    /**
     * @inheritDoc
     */
    public function match(array $path, array $value, Resource $resource): string|false
    {
        $modelType = $value['modelType'] ?? false;
        if ($modelType !== 'html.richText') {
            return false;
        }

        $text = $value['text'] ?? false;
        return is_string($text) ? strip_tags($text) : false;
    }
}
