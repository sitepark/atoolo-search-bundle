<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search\SiteKit;

use Atoolo\Search\Dto\Search\Query\Boosting;

/**
 * Set solr boost definitions for certain fields.
 * This influences the relevance of the fields in the search.
 *
 * The values defined here have been developed through experience in
 * many projects.
 *
 * @codeCoverageIgnore
 */
class DefaultBoosting extends Boosting
{
    public function __construct()
    {
        parent::__construct(
            queryFields: [
                'sp_title^1.4',
                'keywords^1.2',
                'description^1.0',
                'title^1.0',
                'url^0.9',
                'content^0.8',
            ],
            phraseFields: [
                'sp_title^1.5',
                'description^1',
                'content^0.8',
            ],
            boostQueries: [
                'sp_objecttype:searchTip^100',
                'contenttype:(text/html*)^10',
            ],
            boostFunctions: [
                "if(termfreq(sp_objecttype,'news')" .
                    ",scale(sp_date,0,12)" .
                    ",scale(sp_date,10,11)" .
                ")",
            ],
            tie: 0.1,
        );
    }
}
