<?php

declare(strict_types=1);

namespace Atoolo\Search\Console\Command;

use Atoolo\Search\Service\ParameterSolrClientFactory;
use Atoolo\Search\Service\Search\SolrSuggest;

class SolrSuggestBuilder
{
    private string $solrConnectionUrl;

    public function solrConnectionUrl(
        string $solrConnectionUrl
    ): SolrSuggestBuilder {
        $this->solrConnectionUrl = $solrConnectionUrl;
        return $this;
    }

    public function build(): SolrSuggest
    {
        /** @var string[] $url */
        $url = parse_url($this->solrConnectionUrl);
        $clientFactory = new ParameterSolrClientFactory(
            $url['scheme'],
            $url['host'],
            (int)($url['port'] ?? ($url['scheme'] === 'https' ? 443 : 8983)),
            $url['path'] ?? '',
            null,
            0
        );
        return new SolrSuggest($clientFactory);
    }
}
