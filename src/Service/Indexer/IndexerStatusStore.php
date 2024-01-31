<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Search\Dto\Indexer\IndexerStatus;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class IndexerStatusStore
{
    public function __construct(private readonly string $basedir)
    {
    }

    /**
     * @throws ExceptionInterface
     */
    public function load(string $index): IndexerStatus
    {
        $file = $this->getStatusFile($index);

        if (!file_exists($file)) {
            return IndexerStatus::empty();
        }

        $json = file_get_contents($file);
        if ($json === false) {
            throw new InvalidArgumentException('Cannot read file ' . $file);
        }

        /** @var IndexerStatus $status */
        $status = $this
            ->createSerializer()
            ->deserialize($json, IndexerStatus::class, 'json');

        return $status;
    }

    public function store(string $index, IndexerStatus $status): void
    {
        $this->createBaseDirectory();

        $file = $this->getStatusFile($index);
        $json = $this
            ->createSerializer()
            ->serialize($status, 'json');
        $result = file_put_contents($file, $json);
        if ($result === false) {
            throw new RuntimeException(
                'Unable to write indexer-status file ' . $file
            );
        }
    }

    private function createBaseDirectory(): void
    {
        if (
            !is_dir($concurrentDirectory = $this->basedir) &&
            !mkdir($concurrentDirectory) &&
            !is_dir($concurrentDirectory)
        ) {
            throw new RuntimeException(sprintf(
                'Directory "%s" was not created',
                $concurrentDirectory
            ));
        }
    }

    private function createSerializer(): Serializer
    {
        $encoders = [new JsonEncoder()];
        $normalizers = [
            new BackedEnumNormalizer(),
            new DateTimeNormalizer(),
            new PropertyNormalizer()
        ];

        return new Serializer($normalizers, $encoders);
    }

    private function getStatusFile(string $index): string
    {
        return $this->basedir .
            '/atoolo.search.index.' . $index . ".status.json";
    }
}
