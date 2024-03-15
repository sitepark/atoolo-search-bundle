<?php

declare(strict_types=1);

namespace Atoolo\Search\Service;

use Atoolo\Resource\ResourceChannelFactory;

class ResourceChannelBasedCoreName implements SolrCoreName
{
    public function __construct(
        private readonly ResourceChannelFactory $resourceChannelFactory
    ) {
    }

    public function name(?string $locale = null): string
    {
        $resourceChannel = $this->resourceChannelFactory->create();
        if ($locale === null) {
            return $resourceChannel->searchIndex;
        }
        return $resourceChannel->searchIndex . '-' . $locale;
    }
}
