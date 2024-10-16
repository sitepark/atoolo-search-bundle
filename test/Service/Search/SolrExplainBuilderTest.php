<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Search;

use Atoolo\Search\Service\Search\SolrExplainBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SolrExplainBuilder::class)]
class SolrExplainBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $builder = new SolrExplainBuilder();
        $explain = $builder->build([
            'value' => 3.2,
            'description' => 'sum of',
        ]);
        $this->assertEquals(
            [
                'score' => 3.2,
                'type' => 'sum',
                'field' => null,
                'description' => 'sum of',
            ],
            $explain,
            'unexpected explain',
        );
    }

    public function testWithDetails(): void
    {
        $builder = new SolrExplainBuilder();
        $explain = $builder->build([
            "value" => 4.66247,
            "description" => "weight(content:section in 2887) [SchemaSimilarity], result of:",
            "details" => [[
                "match" => true,
                "value" => 4.66247,
                "description" => "score(freq=3.0), computed as boost * idf * tf from:",
            ]],
        ]);
        $this->assertEquals(
            [
                'score' => 4.66247,
                'type' => 'weight',
                'field' => 'content',
                'description' => 'weight(content:section in 2887) [SchemaSimilarity], result of:',
                'details' => [
                    [
                        'score' => 4.66247,
                        'type' => 'score',
                        'field' => null,
                        'description' => 'score(freq=3.0), computed as boost * idf * tf from:',
                    ],
                ],
            ],
            $explain,
            'unexpected explain',
        );
    }

    public function testTypeSum(): void
    {
        $this->assertEquals(
            'sum',
            $this->buildType('sum of'),
            'unexpected type',
        );
    }

    public function testTypeMax(): void
    {
        $this->assertEquals(
            'max',
            $this->buildType('max of'),
            'unexpected type',
        );
    }

    public function testTypeWeight(): void
    {
        $this->assertEquals(
            'weight',
            $this->buildType('weight(content:section in 2892) [SchemaSimilarity], result of:'),
            'unexpected type',
        );
    }

    public function testTypeScore(): void
    {
        $this->assertEquals(
            'score',
            $this->buildType('score(freq=4.0), computed as boost * idf * tf from:"'),
            'unexpected type',
        );
    }

    public function testTypeIdf(): void
    {
        $this->assertEquals(
            'idf',
            $this->buildType('idf, computed as log(1 + (N - n + 0.5) / (n + 0.5)) from:'),
            'unexpected type',
        );
    }

    public function testTypeTf(): void
    {
        $this->assertEquals(
            'tf',
            $this->buildType('tf, computed as freq / (freq + k1 * (1 - b + b * dl / avgdl)) from:'),
            'unexpected type',
        );
    }

    public function testTypeFunction(): void
    {
        $this->assertEquals(
            'function',
            $this->buildType('FunctionQuery(content:section), product of:'),
            'unexpected type',
        );
    }

    public function testTypeBoost(): void
    {
        $this->assertEquals(
            'boost',
            $this->buildType('boost(1.0), product of:'),
            'unexpected type',
        );
    }

    public function testTypeDocFrequency(): void
    {
        $this->assertEquals(
            'docFrequency',
            $this->buildType('n, number of documents containing term'),
            'unexpected type',
        );
    }

    public function testTypeDocCount(): void
    {
        $this->assertEquals(
            'docCount',
            $this->buildType('N, total number of documents with field'),
            'unexpected type',
        );
    }

    public function testTypeTermFrequency(): void
    {
        $this->assertEquals(
            'termFrequency',
            $this->buildType('freq, occurrences of term within document'),
            'unexpected type',
        );
    }

    public function testTypeFieldLength(): void
    {
        $this->assertEquals(
            'fieldLength',
            $this->buildType('fieldLength in 2892'),
            'unexpected type',
        );
    }

    public function testTypeAverageFieldLength(): void
    {
        $this->assertEquals(
            'averageFieldLength',
            $this->buildType('avgdl, average length of field'),
            'unexpected type',
        );
    }

    public function testTypeQueryNorm(): void
    {
        $this->assertEquals(
            'queryNorm',
            $this->buildType('queryNorm, product of:'),
            'unexpected type',
        );
    }

    public function testDefaultType(): void
    {
        $this->assertEquals(
            'boosting',
            $this->buildType('other'),
            'unexpected type',
        );
    }

    private function buildType(string $description): string
    {
        $builder = new SolrExplainBuilder();
        $explain = $builder->build([
            'value' => 3.2,
            'description' => $description,
        ]);
        return $explain['type'];
    }
}
