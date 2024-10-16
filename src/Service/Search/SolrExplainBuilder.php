<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Search;

/**
 * @phpstan-type SolrExplainDetail array{
 *   value?: float,
 *   description?: string,
 *   details?: array<array<string,mixed>>,
 * }
 * @phpstan-type ExplainDetail array{
 *   score: float,
 *   type: string,
 *   field: string|null,
 *   description: string,
 *   details?: array<array<string,mixed>>,
 * }
 */
class SolrExplainBuilder
{
    /**
     * @param SolrExplainDetail $explain
     * @return ExplainDetail
     */
    public function build(array $explain): array
    {
        return $this->buildDetail($explain);
    }

    /**
     * @param SolrExplainDetail $detail
     * @return ExplainDetail
     */
    private function buildDetail(array $detail): array
    {

        $description = $detail['description'] ?? '';

        $modifiedDetails = [];
        $modifiedDetails['score'] = $detail['value'] ?? 0;

        $type = $this->identifyType($description);
        $modifiedDetails['type'] = $type;

        $modifiedDetails['field'] = $this->parseField($description);
        $modifiedDetails['description'] = $description;

        $modifiedSubDetails = [];
        foreach ($detail['details'] ?? [] as $subDetail) {
            /** @var SolrExplainDetail $subDetail */
            $modifiedSubDetails[] = $this->buildDetail($subDetail);
        }
        if (!empty($modifiedSubDetails)) {
            $modifiedDetails['details'] = $modifiedSubDetails;
        }

        return $modifiedDetails;
    }

    private function identifyType(string $description): string
    {

        switch (true) {
            case str_starts_with($description, 'sum of'):
                return 'sum';
            case str_starts_with($description, 'max '): // 'max of' | 'max plus'
                return 'max';
            case str_starts_with($description, 'weight'):
                return 'weight';
            case str_starts_with($description, 'score'):
                return 'score';
            case str_starts_with($description, 'idf, '):
            case str_starts_with($description, 'idf(), sum of'):
                return 'idf';
            case str_starts_with($description, 'tfNorm, ') || strpos($description, 'tf, ') === 0:
                return 'tf';
            case str_starts_with($description, 'FunctionQuery'):
                return 'function';
            case str_starts_with($description, 'boost'):
                return 'boost';
            case stripos($description, 'docFreq') === 0 || stripos(
                $description,
                'n, number of documents containing term',
            ) === 0:
                return 'docFrequency';
            case stripos($description, 'docCount') === 0 || stripos(
                $description,
                'N, total number of documents with field',
            ) === 0:
                return 'docCount';
            case stripos($description, 'termFreq') === 0 || stripos(
                $description,
                'freq, occurrences of term within document',
            ) === 0:
                return 'termFrequency';
            case stripos($description, 'fieldLength') === 0 || stripos($description, 'dl, length of field') === 0:
                return 'fieldLength';
            case stripos($description, 'avgFieldLength') === 0 || stripos(
                $description,
                'avgdl, average length of field',
            ) === 0:
                return 'averageFieldLength';
            case stripos($description, 'queryNorm') === 0:
                return 'queryNorm';
            default:
                return 'boosting';
        }
    }

    private function parseField(string $description): ?string
    {
        if (strpos($description, 'weight') !== false) {
            $matches = [];
            preg_match_all('/weight\((.*):(.*)\)/', $description, $matches);
            return $matches[1][0];
        }
        return null;
    }
}
