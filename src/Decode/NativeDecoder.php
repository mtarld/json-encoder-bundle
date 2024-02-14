<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Decode;

use Mtarld\JsonEncoderBundle\Exception\UnexpectedValueException;
use Mtarld\JsonEncoderBundle\Stream\StreamReaderInterface;

/**
 * Decodes string or stream using the native "json_decode" PHP function.
 *
 * @internal
 */
final readonly class NativeDecoder
{
    public static function decodeString(string $json): mixed
    {
        try {
            return json_decode($json, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new UnexpectedValueException('JSON is not valid.');
        }
    }

    /**
     * @param StreamReaderInterface|resource $stream
     */
    public static function decodeStream(mixed $stream, int $offset = 0, ?int $length = null): mixed
    {
        if (\is_resource($stream)) {
            $json = stream_get_contents($stream, $length ?? -1, $offset);
        } else {
            $stream->seek($offset);
            $json = $stream->read($length);
        }

        try {
            return json_decode($json, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new UnexpectedValueException('JSON is not valid.');
        }
    }
}
