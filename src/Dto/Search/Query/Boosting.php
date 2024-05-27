<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Search\Query;

/**
 * Boosting is used to influence the relevance of documents.
 * The parameters must be strongly adapted to the search engine used and
 * require in-depth knowledge of the schema.
 *
 * @codeCoverageIgnore
 */
class Boosting
{
    /**
     * @param array<string> $queryFields List of fields and the "boosts"
     *  to associate with each of them when
     *  building DisjunctionMaxQueries from the user's query.
     * @param array<string> $phraseFields This param can be used to "boost"
     *  the score of documents in cases where all of the terms in the "q"
     *  param appear in close proximity.
     * @param array<string> $boostQueries an additional, query clause that
     *  will be added to the main query to influence the score.
     * @param array<string> $boostFunctions Functions (with optional boosts)
     *  that will be included in the query to influence the score.
     * @param float $tie The tie parameter is used to control how much
     *  the score of the non-phrase query should influence the score of
     *  the phrase query.
     */
    public function __construct(
        public readonly array $queryFields = [],
        public readonly array $phraseFields = [],
        public readonly array $boostQueries = [],
        public readonly array $boostFunctions = [],
        public readonly float $tie = 0.0,
    ) {
    }
}
