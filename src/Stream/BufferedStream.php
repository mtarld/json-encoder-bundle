<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Stream;

/**
 * Stream that is stored in memory.
 */
final class BufferedStream implements StreamReaderInterface, StreamWriterInterface
{
    private const CHUNK_LENGTH = 8192;

    private string $buffer = '';
    private int $offset = 0;

    public function __construct()
    {
    }

    public function read(?int $length = null): string
    {
        $data = substr($this->buffer, $this->offset, $length ?? self::CHUNK_LENGTH);
        $this->offset += \strlen($data);

        return $data;
    }

    public function seek(int $offset): void
    {
        $this->offset = $offset;
    }

    public function rewind(): void
    {
        $this->offset = 0;
    }

    public function getIterator(): \Traversable
    {
        foreach (str_split(substr($this->buffer, $this->offset), self::CHUNK_LENGTH) as $chunk) {
            yield $chunk;
        }
    }

    public function __toString(): string
    {
        return substr($this->buffer, $this->offset);
    }

    public function write(string $string): void
    {
        $this->buffer .= $string;
        $this->offset = \strlen($this->buffer);
    }
}
