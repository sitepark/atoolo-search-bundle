<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Resource\ResourceLanguage;
use Atoolo\Search\Dto\Search\Query\Filter\ObjectTypeFilter;
use Atoolo\Search\Dto\Search\Query\SuggestQuery;
use Atoolo\Search\Dto\Search\Result\Suggestion;
use Atoolo\Search\Exception\UnexpectedResultException;
use Atoolo\Search\Service\IndexName;
use Atoolo\Search\Service\Search\QueryTemplateResolver;
use Atoolo\Search\Service\Search\Schema2xFieldMapper;
use Atoolo\Search\Service\Search\SolrSuggest;
use Atoolo\Search\Service\SolrClientFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Solarium\Client;
use Solarium\Core\Client\Response;
use Solarium\QueryType\Select\Query\Query as SolrSelectQuery;
use Solarium\QueryType\Select\Result\Result as SelectResult;

#[CoversClass(SolrSuggest::class)]
class SolrSuggestTest extends TestCase
{
    private SelectResult|Stub $result;

    private SolrSuggest $searcher;

    private Client&MockObject $client;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {

        $this->result = $this->createStub(SelectResult::class);
        $this->client = $this->createConfiguredMock(Client::class, [
            'createSelect' => new SolrSelectQuery(),
            'select' => $this->result,
        ]);

        $clientFactory = $this->createConfiguredMock(
            SolrClientFactory::class,
            [
                'create' => $this->client,
            ],
        );


        $schemaFieldMapper = $this->createStub(Schema2xFieldMapper::class);
        $queryTemplateResolver = $this->createStub(QueryTemplateResolver::class);
        $indexName = $this->createStub(IndexName::class);

        $this->searcher = new SolrSuggest(
            $indexName,
            $clientFactory,
            $schemaFieldMapper,
            $queryTemplateResolver,
        );
    }

    public function testSuggest(): void
    {
        $filter = new ObjectTypeFilter(['test']);

        $query = new SuggestQuery(
            'cat',
            ResourceLanguage::default(),
            [$filter],
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
            'unexpected suggestion',
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
            'suggestion should be empty',
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

    public function testCaseInsensitivity(): void
    {
        $query = new SuggestQuery(
            'Test',
            ResourceLanguage::default(),
        );

        $response = new Response(<<<END
            {
                "facet_counts" : {
                }
            }
        END);
        $this->result->method('getResponse')->willReturn($response);

        $this->client->expects($this->once())
            ->method('select')
            ->with($this->callback(function (SolrSelectQuery $q) {
                $prefix = $q->getParams()['facet.prefix'] ?? '';
                return $prefix === 'test';
            }));

        $this->searcher->suggest($query);

    }
}
