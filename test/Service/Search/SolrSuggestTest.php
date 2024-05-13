<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Filter\ObjectTypeFilter;
use Atoolo\Search\Dto\Search\Query\SuggestQuery;
use Atoolo\Search\Dto\Search\Result\Suggestion;
use Atoolo\Search\Exception\UnexpectedResultException;
use Atoolo\Search\Service\IndexName;
use Atoolo\Search\Service\Search\Schema2xFieldMapper;
use Atoolo\Search\Service\Search\SolrSuggest;
use Atoolo\Search\Service\SolrClientFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Solarium\Client;
use Solarium\Core\Client\Response;
use Solarium\QueryType\Select\Query\FilterQuery;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;
use Solarium\QueryType\Select\Result\Result as SelectResult;

#[CoversClass(SolrSuggest::class)]
class SolrSuggestTest extends TestCase
{
    private SelectResult|Stub $result;

    private SolrSuggest $searcher;

    protected function setUp(): void
    {
        $indexName = $this->createStub(IndexName::class);
        $clientFactory = $this->createStub(
            SolrClientFactory::class
        );
        $client = $this->createStub(Client::class);
        $clientFactory->method('create')->willReturn($client);

        $query = $this->createStub(SolrSelectQuery::class);

        $query->method('createFilterQuery')
            ->willReturn(new FilterQuery());

        $client->method('createSelect')->willReturn($query);

        $this->result = $this->createStub(SelectResult::class);
        $client->method('select')->willReturn($this->result);

        $schemaFieldMapper = $this->createStub(
            Schema2xFieldMapper::class
        );

        $this->searcher = new SolrSuggest(
            $indexName,
            $clientFactory,
            $schemaFieldMapper
        );
    }

    public function testSuggest(): void
    {
        $filter = new ObjectTypeFilter(['test']);

        $query = new SuggestQuery(
            'cat',
            ResourceLanguage::default(),
            [$filter]
        );

        $response = new Response(<<<END
{
    "facet_counts" : {
        "facet_fields" : {
            "raw_content" : [
                "category",
                10,
                "catalog",
                5
            ]
        }
    }
}
END);

        $this->result->method('getResponse')->willReturn($response);

        $suggestResult = $this->searcher->suggest($query);

        $expected = [
            new Suggestion('category', 10),
            new Suggestion('catalog', 5),
        ];

        $this->assertEquals(
            $expected,
            $suggestResult->suggestions,
            'unexpected suggestion'
        );
    }

    public function testEmptySuggest(): void
    {
        $query = new SuggestQuery(
            'cat',
            ResourceLanguage::default(),
        );

        $response = new Response(<<<END
{
    "facet_counts" : {
    }
}
END);

        $this->result->method('getResponse')->willReturn($response);

        $suggestResult = $this->searcher->suggest($query);

        $this->assertEmpty(
            $suggestResult->suggestions,
            'suggestion should be empty'
        );
    }

    public function testInvalidSuggestResponse(): void
    {
        $query = new SuggestQuery(
            'cat',
            ResourceLanguage::default(),
        );

        $response = new Response("none json");

        $this->result->method('getResponse')->willReturn($response);

        $this->expectException(UnexpectedResultException::class);
        $this->searcher->suggest($query);
    }
}
