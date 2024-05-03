<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Service;

use Atoolo\Search\Service\ParameterSolrClientFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ParameterSolrClientFactory::class)]
class SolrParameterClientFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $factory = new ParameterSolrClientFactory(
            'http',
            'localhost',
            8080
        );
        $client = $factory->create('myindex');
        $this->assertNotNull($client, 'client instance expected');
    }
}
