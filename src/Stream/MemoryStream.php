<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Stream;

/**
 * Opens and holds a "php://memory" resource.
 */
final class MemoryStream implements StreamReaderInterface, StreamWriterInterface
{
    private const CHUNK_LENGTH = 8192;

    /**
     * @var resource
     */
    private mixed $resource;

    public function __construct()
    {
        $this->resource = fopen('php://memory', 'w+');
    }

    public function __destruct()
    {
        fclose($this->resource);
    }

    public function read(?int $length = null): string
    {
        return fread($this->resource, $length ?? self::CHUNK_LENGTH);
    }

    public function seek(int $offset): void
    {
        fseek($this->resource, $offset);
    }

    public function rewind(): void
    {
        rewind($this->resource);
    }

    public function getIterator(): \Traversable
    {
        while (!feof($this->resource)) {
            yield fread($this->resource, self::CHUNK_LENGTH);
        }
    }

    public function __toString(): string
    {
        return stream_get_contents($this->resource);
    }

    public function write(string $string): void
    {
        fwrite($this->resource, $string);
    }

    public function getResource(): mixed
    {
        return $this->resource;
    }
}
