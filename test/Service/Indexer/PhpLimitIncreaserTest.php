<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Search\Service\Indexer\PhpLimitIncreaser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpLimitIncreaser::class)]
class PhpLimitIncreaserTest extends TestCase
{
    private ?string $savedTimeLimit = null;
    private ?string $savedMemoryLimit = null;

    public function setUp(): void
    {
        $this->savedTimeLimit = ini_get('max_execution_time') ?: '0';
        $this->savedMemoryLimit = ini_get('memory_limit') ?:
            PhpLimitIncreaser::UNLIMITED_MEMORY;
    }

    public function tearDown(): void
    {
        set_time_limit((int) $this->savedTimeLimit);
        ini_set('memory_limit', $this->savedMemoryLimit);
    }

    public function testIncreaseTimeLimit(): void
    {
        $increaser = new PhpLimitIncreaser(
            60 * 60 * 2,
            '',
        );
        $increaser->increase();

        $this->assertEquals(
            60 * 60 * 2,
            ini_get('max_execution_time'),
            'max_execution_time does not match expected value',
        );

        $this->assertEquals(
            $this->savedMemoryLimit,
            (string) ini_get('memory_limit'),
            'memory_limit does not match expected value',
        );
    }

    public function testResetTimeLimit(): void
    {
        $increaser = new PhpLimitIncreaser(
            60 * 60 * 2,
            '',
        );
        $increaser->increase();
        $increaser->reset();

        $this->assertEquals(
            $this->savedTimeLimit,
            (string) ini_get('max_execution_time'),
            'max_execution_time does not match expected value',
        );
    }

    public function testTimeLimitIsLower(): void
    {
        $increaser = new PhpLimitIncreaser(
            0,
            '',
        );
        $increaser->increase();

        $this->assertEquals(
            $this->savedTimeLimit,
            (string) ini_get('max_execution_time'),
            'max_execution_time does not match expected value',
        );
    }

    public function testIncreaseMemoryLimit(): void
    {
        $increaser = new PhpLimitIncreaser(
            0,
            '10G',
        );
        $increaser->increase();

        $this->assertEquals(
            '10G',
            ini_get('memory_limit'),
            'memory_limit does not match expected value',
        );
    }

    public function testIncreaseMemoryLimitWhenAlreadyUnlimited(): void
    {
        ini_set('memory_limit', PhpLimitIncreaser::UNLIMITED_MEMORY);

        $increaser = new PhpLimitIncreaser(
            0,
            '10G',
        );
        $increaser->increase();

        $this->assertEquals(
            PhpLimitIncreaser::UNLIMITED_MEMORY,
            ini_get('memory_limit'),
            'memory_limit does not match expected value',
        );
    }

    public function testIncreaseToUnlimitedMemoryLimit(): void
    {
        $increaser = new PhpLimitIncreaser(
            0,
            PhpLimitIncreaser::UNLIMITED_MEMORY,
        );
        $increaser->increase();

        $this->assertEquals(
            PhpLimitIncreaser::UNLIMITED_MEMORY,
            ini_get('memory_limit'),
            'memory_limit does not match expected value',
        );
    }

    public function testResetMemoryLimit(): void
    {
        $increaser = new PhpLimitIncreaser(
            0,
            '512M',
        );
        $increaser->increase();
        $increaser->reset();

        $this->assertEquals(
            $this->savedMemoryLimit,
            (string) ini_get('memory_limit'),
            'memory_limit does not match expected value',
        );
    }

    public function testMemoryLimitIsLower(): void
    {
        $increaser = new PhpLimitIncreaser(
            0,
            '1',
        );
        $increaser->increase();

        $this->assertEquals(
            $this->savedMemoryLimit,
            (string) ini_get('memory_limit'),
            'memory_limit does not match expected value',
        );
    }

    public function testWithInvalidMemoryLimit(): void
    {
        $increaser = new PhpLimitIncreaser(
            0,
            '1X',
        );
        $increaser->increase();

        $this->assertEquals(
            $this->savedMemoryLimit,
            (string) ini_get('memory_limit'),
            'memory_limit does not match expected value',
        );
    }
}
