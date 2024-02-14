<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Stream;

/**
 * Reads stream data sequentially.
 *
 * @extends \IteratorAggregate<string>
 */
interface StreamReaderInterface extends \IteratorAggregate, \Stringable
{
    public function read(?int $length = null): string;

    public function seek(int $offset): void;

    public function rewind(): void;
}
