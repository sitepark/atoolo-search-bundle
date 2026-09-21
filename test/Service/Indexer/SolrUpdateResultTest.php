<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service\Indexer;

use Atoolo\Search\Service\Indexer\SolrUpdateResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solarium\Core\Client\Response;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;

#[CoversClass(SolrUpdateResult::class)]
class SolrUpdateResultTest extends TestCase
{
    public function testIsSuccess(): void
    {
        $this->assertTrue(
            $this->createResult(0, 'OK')->isSuccess(),
            'status 0 should be a success',
        );
    }

    public function testIsNotSuccess(): void
    {
        $this->assertFalse(
            $this->createResult(500, 'Server Error')->isSuccess(),
            'status 500 should not be a success',
        );
    }

    public function testGetErrorMessage(): void
    {
        $this->assertEquals(
            'Server Error',
            $this->createResult(500, 'Server Error')->getErrorMessage(),
            'unexpected error message',
        );
    }

    private function createResult(int $status, string $message): SolrUpdateResult
    {
        $response = new Response(
            json_encode(['responseHeader' => ['status' => $status]]),
            ['HTTP/1.1 200 ' . $message, 'Content-Type: application/json'],
        );

        return new SolrUpdateResult(new UpdateQuery(), $response);
    }
}
