<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer\SiteKit;

use Atoolo\Resource\Resource;
use Soundasleep\Html2Text;
use Soundasleep\Html2TextException;

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
        $convertOptions = [
            'ignore_errors' => true,
            'drop_links' => true,
        ];
        return is_string($text) ? Html2Text::convert($text, $convertOptions) : false;
    }
}
