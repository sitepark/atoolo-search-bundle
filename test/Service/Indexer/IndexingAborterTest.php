<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Search\Service\Indexer\IndexingAborter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IndexingAborter::class)]
class IndexingAborterTest extends TestCase
{
    private string $file;
    private IndexingAborter $aborter;

    public function setUp(): void
    {
        $workdir = __DIR__ .
            '/../../../var/test/IndexingAborterTest';
        if (!is_dir($workdir)) {
            mkdir($workdir, 0777, true);
        }
        $this->file = $workdir . '/background-indexer-test.abort';
        if (file_exists($this->file)) {
            unlink($this->file);
        }
        $this->aborter = new IndexingAborter($workdir, 'background-indexer');
    }

    public function testShouldNotAborted(): void
    {
        $this->assertFalse(
            $this->aborter->shouldAborted('test'),
            'should not aborted'
        );
    }
    public function testShouldAborted(): void
    {
        touch($this->file);
        $this->assertTrue(
            $this->aborter->shouldAborted('test'),
            'should not aborted'
        );
    }

    public function testAbort(): void
    {
        $this->aborter->abort('test');
        $this->assertFileExists(
            $this->file,
            'abort call should create file'
        );
    }

    public function testAborted(): void
    {
        touch($this->file);
        $this->aborter->aborted('test');
        $this->assertFileDoesNotExist(
            $this->file,
            'aborted call should remove file'
        );
    }
}
