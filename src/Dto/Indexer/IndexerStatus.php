<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

use DateTime;
use JsonException;

class IndexerStatus
{
    public function __construct(
        public readonly DateTime $startTime,
        public ?DateTime $endTime,
        public int $total,
        public int $processed,
        public int $errors
    ) {
    }

    public static function empty(): IndexerStatus
    {
        $now = new DateTime();
        return new IndexerStatus(
            $now,
            $now,
            0,
            0,
            0
        );
    }

    public function getStatusLine(): string
    {
        $endTime = $this->endTime;
        if ($endTime === null) {
            $endTime = new DateTime();
        }
        $duration = $this->startTime->diff($endTime);
        return
            'start: ' . $this->startTime->format('d.m.Y H:i') . ', ' .
            'time: ' . $duration->format('%Hh %Im %Ss') . ', ' .
            'processed: ' . $this->processed . "/" . $this->total . ', ' .
            'errors: ' . $this->errors;
    }

    /**
     * @throws JsonException
     */
    public static function load(string $file): IndexerStatus
    {
        if (!file_exists($file)) {
            return self::empty();
        }
        $content = file_get_contents($file);
        $data = json_decode(
            $content,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $startTime = new DateTime();
        $startTime->setTimestamp($data['startTime']);

        $endTime = null;
        if ($data['endTime'] !== null) {
            $endTime = new DateTime();
            $endTime->setTimestamp($data['endTime']);
        }

        return new IndexerStatus(
            $startTime,
            $endTime,
            $data['total'],
            $data['processed'],
            $data['errors']
        );
    }

    /**
     * @throws JsonException
     */
    public function store(string $file): void
    {
        $jsonString = json_encode([
            'statusLine' => $this->getStatusLine(),
            'startTime' => $this->startTime->getTimestamp(),
            'endTime' => $this->endTime?->getTimestamp(),
            'total' => $this->total,
            'processed' => $this->processed,
            'errors' => $this->errors
        ], JSON_THROW_ON_ERROR);
        file_put_contents($file, $jsonString);
    }
}
