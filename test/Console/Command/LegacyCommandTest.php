<?php

declare(strict_types=1);

namespace Atoolo\Search\Test\Console\Command;

use Atoolo\Index\Console\Application;
use Atoolo\Index\Console\Command\Io\IndexerProgressBar;
use Atoolo\Index\Service\Indexer\IndexDocument;
use Atoolo\Index\Service\Indexer\IndexDocumentDumper;
use Atoolo\Index\Service\Indexer\IndexDocumentDumperCollection;
use Atoolo\Index\Service\Indexer\IndexerCollection;
use Atoolo\Index\Service\Indexer\UpdatableIndexer;
use Atoolo\Search\Console\Command\DumpIndexDocument;
use Atoolo\Search\Console\Command\Indexer;
use Atoolo\Search\Console\Command\IndexerInternalResourceUpdate;
use Atoolo\Resource\DataBag;
use Atoolo\Resource\ResourceChannel;
use Atoolo\Resource\ResourceTenant;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The commands of the search-bundle keep their old names and delegate to the
 * commands of the index-bundle. Removed in 2.0.
 */
class LegacyCommandTest extends TestCase
{
    private ResourceChannel $resourceChannel;

    public function setUp(): void
    {
        $this->resourceChannel = new ResourceChannel(
            '',
            'WWW',
            '',
            '',
            false,
            '',
            '',
            '',
            '',
            '',
            'test',
            [],
            new DataBag([]),
            $this->createMock(ResourceTenant::class),
        );
    }

    public function testIndexerKeepsOldName(): void
    {
        $command = new Indexer(
            $this->resourceChannel,
            $this->createStub(IndexerProgressBar::class),
            new IndexerCollection([]),
        );
        $application = new Application([$command]);

        $this->assertSame(
            $command,
            $application->find('search:indexer'),
            'the deprecated command name should still resolve',
        );
    }

    public function testUpdateKeepsOldName(): void
    {
        $command = new IndexerInternalResourceUpdate(
            $this->resourceChannel,
            $this->createStub(IndexerProgressBar::class),
            new IndexerCollection([]),
        );
        $application = new Application([$command]);

        $this->assertSame(
            $command,
            $application->find('search:indexer:update-internal-resources'),
            'the deprecated command name should still resolve',
        );
    }

    public function testDumpPinsInternalSource(): void
    {
        $internal = $this->createStub(IndexDocumentDumper::class);
        $internal->method('getSource')->willReturn('internal');
        $internal->method('dump')->willReturn([
            $this->createDocument(['sp_id' => '123']),
        ]);

        $other = $this->createStub(IndexDocumentDumper::class);
        $other->method('getSource')->willReturn('genai');
        $other->method('dump')->willReturn([
            $this->createDocument(['id' => '123']),
        ]);

        $command = new DumpIndexDocument(
            $this->resourceChannel,
            new IndexDocumentDumperCollection([$internal, $other]),
        );
        $application = new Application([$command]);

        $tester = new CommandTester(
            $application->find('search:dump-index-document'),
        );
        $tester->execute(['paths' => ['test.php']]);
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(
            '"sp_id": "123"',
            $tester->getDisplay(),
            'the solr document should be dumped without asking for a source',
        );
    }

    /**
     * @param array<string,mixed> $data
     */
    private function createDocument(array $data): IndexDocument
    {
        $document = $this->createStub(IndexDocument::class);
        $document->method('jsonSerialize')->willReturn($data);
        return $document;
    }

    public function testDeprecationNoticeIsPrinted(): void
    {
        $command = new Indexer(
            $this->resourceChannel,
            $this->createStub(IndexerProgressBar::class),
            new IndexerCollection([]),
        );
        $application = new Application([$command]);

        $tester = new CommandTester($application->find('search:indexer'));
        $tester->execute([], ['capture_stderr_separately' => true]);

        $this->assertStringContainsString(
            'use "index:indexer" instead',
            $tester->getErrorOutput(),
            'the rename notice should go to stderr',
        );
    }

    public function testUpdateNoticeIsPrinted(): void
    {
        $indexer = $this->createStub(UpdatableIndexer::class);
        $indexer->method('enabled')->willReturn(true);
        $indexer->method('getSource')->willReturn('internal');
        $indexer->method('getName')->willReturn('Internal');

        $command = new IndexerInternalResourceUpdate(
            $this->resourceChannel,
            $this->createStub(IndexerProgressBar::class),
            new IndexerCollection([$indexer]),
        );
        $application = new Application([$command]);

        $tester = new CommandTester(
            $application->find('search:indexer:update-internal-resources'),
        );
        $tester->execute(
            ['paths' => ['a.php']],
            ['capture_stderr_separately' => true],
        );
        $tester->assertCommandIsSuccessful();

        $this->assertStringContainsString(
            'use "index:update" instead',
            $tester->getErrorOutput(),
            'the rename notice should go to stderr',
        );
    }
}
