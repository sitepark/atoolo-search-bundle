<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query\Filter;

/**
 * @codeCoverageIgnore
 */
class TeaserPropertyFilter extends Filter
{
    public function __construct(
        public readonly ?bool $image,
        public readonly ?bool $imageCopyright,
        public readonly ?bool $headline,
        public readonly ?bool $text,
        ?string $key = null,
    ) {
        parent::__construct(
            $key,
            $key !== null ? [$key] : [],
        );
    }
}
