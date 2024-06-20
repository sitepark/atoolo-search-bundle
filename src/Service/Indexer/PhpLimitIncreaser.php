<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

class PhpLimitIncreaser
{
    public const UNLIMITED_MEMORY = '-1';
    private string $savedTimeLimit;
    private string $savedMemoryLimit;

    public function __construct(
        private readonly int $timeLimit,
        private readonly string $memoryLimit
    ) {
    }

    public function increase(): void
    {
        $this->savedTimeLimit =
            ini_get('max_execution_time') ?: '0';
        $this->savedMemoryLimit =
            ini_get('memory_limit') ?: self::UNLIMITED_MEMORY;

        if (
            ((int)$this->savedTimeLimit) < $this->timeLimit
        ) {
            set_time_limit($this->timeLimit);
        }

        if ($this->isLowerMemory($this->savedMemoryLimit, $this->memoryLimit)) {
            ini_set('memory_limit', $this->memoryLimit);
        }
    }

    public function reset(): void
    {
        set_time_limit((int)$this->savedTimeLimit);
        ini_set('memory_limit', $this->savedMemoryLimit);
    }

    private function isLowerMemory(string $current, string $limit): bool
    {
        if ($current === $limit) {
            return false;
        }

        //
        if ($current === self::UNLIMITED_MEMORY) {
            return false;
        }
        if ($limit === self::UNLIMITED_MEMORY) {
            return true;
        }
        return $this->toMemoryStringToInteger($current) <
            $this->toMemoryStringToInteger($limit);
    }

    private function toMemoryStringToInteger(string $memory): int
    {
        [$number, $suffix] = sscanf($memory, '%u%c') ?? [null, null];
        if (!is_string($suffix)) {
            return (int)$memory;
        }

        $pos = stripos(' KMG', $suffix);
        if (!is_int($pos) || !is_int($number)) {
            return 0;
        }
        return $number * (1024 ** $pos);
    }
}
