<?php

declare(strict_types=1);

namespace Atoolo\Search\Service\Indexer;

use Atoolo\Resource\ResourceChannel;
use Exception;
use LogicException;
use RuntimeException;
use XMLReader;

class SolrXmlReader
{
    private ?XMLReader $xmlReader = null;

    private string $xmlUri;

    public function __construct(
        private readonly ResourceChannel $resourceChannel,
    ) {}

    public function open(string $xmlUri): void
    {
        if (
            !filter_var($xmlUri, FILTER_VALIDATE_URL)
            && !file_exists($xmlUri)
        ) {
            $xmlUri = $this->resourceChannel->baseDir . '/media/public' . $xmlUri;
        }
        $this->xmlUri = $xmlUri;

        $this->xmlReader?->close();

        $reader = @XmlReader::open($this->xmlUri);
        if ($reader === false) {
            throw new RuntimeException('Could not open XML URI ' . $this->xmlUri);
        }
        $this->xmlReader = $reader;
    }

    public function count(): int
    {
        if ($this->xmlReader === null) {
            throw new LogicException('XML file is not open');
        }
        $count = 0;
        while ($this->xmlReader->read()) {
            if (
                $this->nodeType() === XMLReader::ELEMENT
                && $this->tagName() === 'doc'
            ) {
                $count++;
            }
        }
        $this->open($this->xmlUri); // reset the reader (close and open again)
        return $count;
    }

    /**
     * @param int $count
     * @return array<array<string,string|array<string>>>
     * @throws Exception
     */
    public function next(int $count): array
    {
        if ($this->xmlReader === null) {
            throw new LogicException('XML file is not open');
        }

        $docList = [];
        while ($this->xmlReader->read()) {
            if (
                $this->nodeType() === XMLReader::ELEMENT
                && $this->tagName() === 'doc'
            ) {
                $docList[] = $this->readDoc();
                if (count($docList) === $count) {
                    break;
                }
            }
        }
        return $docList;
    }

    /**
     * @throws Exception
     * @return array<string,string|array<string>>
     */
    private function readDoc(): array
    {

        // cannot occur, but phpstan is then happy
        // @codeCoverageIgnoreStart
        if ($this->xmlReader === null) {
            throw new LogicException('XML file is not open');
        }
        // @codeCoverageIgnoreEnd

        $doc = [];
        while (@$this->xmlReader->read()) {
            if (
                $this->nodeType() === XMLReader::END_ELEMENT
                && $this->tagName() === 'doc'
            ) {
                return $doc;
            }

            if (
                $this->nodeType() !== XMLReader::ELEMENT
            ) {
                continue;
            }

            if ($this->tagName() !== 'field') {
                continue;
            }

            $name = $this->xmlReader->getAttribute('name');
            $value = $this->xmlReader->readString();

            if (!isset($doc[$name])) {
                $doc[$name] = $value;
            } else {
                if (!is_array($doc[$name])) {
                    $doc[$name] = [$doc[$name]];
                }
                $doc[$name][] = $value;
            }
        }
        throw new RuntimeException('Unexpected end of XML');
    }

    private function nodeType(): int
    {
        return $this->xmlReader->nodeType ?? XMLReader::NONE;
    }

    private function tagName(): ?string
    {
        return $this->xmlReader->name ?? null;
    }
}
