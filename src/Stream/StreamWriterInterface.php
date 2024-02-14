<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Stream;

/**
 * Writes data into stream.
 */
interface StreamWriterInterface
{
    public function write(string $string): void;
}
