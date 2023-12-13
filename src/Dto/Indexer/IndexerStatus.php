<?php

declare(strict_types=1);

namespace Atoolo\Search\Dto\Indexer;

use DateTime;
use JsonException;

class IndexerStatus
{
    public function __construct(
        public IndexerStatusState $state,
        public readonly DateTime $startTime,
        public ?DateTime $endTime,
        public int $total,
        public int $processed,
        public int $skipped,
        public DateTime $lastUpdate,
        public int $updated,
        public int $errors
    ) {
    }

    public static function empty(): IndexerStatus
    {
        $now = new DateTime();
        return new IndexerStatus(
            IndexerStatusState::UNKNOWN,
            $now,
            $now,
            0,
            0,
            0,
            $now,
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
            '[' . $this->state->name . '] ' .
            'start: ' . $this->startTime->format('d.m.Y H:i') . ', ' .
            'time: ' . $duration->format('%Hh %Im %Ss') . ', ' .
            'processed: ' . $this->processed . "/" . $this->total . ', ' .
            'skipped: ' . $this->skipped . ', ' .
            'lastUpdate: ' . $this->startTime->format('d.m.Y H:i') . ', ' .
            'updated: ' . $this->updated . ', ' .
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

        $state = isset($data['state'])
            ? IndexerStatusState::valueOf($data['state'])
            : IndexerStatusState::UNKNOWN;

        $startTime = new DateTime();
        $startTime->setTimestamp($data['startTime']);

        $endTime = new DateTime();
        if ($data['endTime'] !== null) {
            $endTime->setTimestamp($data['endTime']);
        } else {
            $endTime->setTimestamp(0);
        }

        $lastUpdate = new DateTime();
        if ($data['lastUpdate'] !== null) {
            $lastUpdate->setTimestamp($data['lastUpdate']);
        } else {
            $lastUpdate->setTimestamp(0);
        }

        return new IndexerStatus(
            $state,
            $startTime,
            $endTime,
            $data['total'],
            $data['processed'],
            $data['skipped'] ?? 0,
            $lastUpdate,
            $data['updated'] ?? 0,
            $data['errors'] ?? 0
        );
    }

    /**
     * @throws JsonException
     */
    public function store(string $file): void
    {
        $jsonString = json_encode([
            'state' => $this->state->name,
            'statusLine' => $this->getStatusLine(),
            'startTime' => $this->startTime->getTimestamp(),
            'endTime' => $this->endTime?->getTimestamp(),
            'total' => $this->total,
            'processed' => $this->processed,
            'skipped' => $this->skipped,
            'lastUpdate' => $this->lastUpdate->getTimestamp(),
            'updated' => $this->updated,
            'errors' => $this->errors
        ], JSON_THROW_ON_ERROR);
        file_put_contents($file, $jsonString);
    }
}
