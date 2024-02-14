<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle;

/**
 * Represents an encoding result.
 * Can be iterated or casted to string.
 *
 * @implements \IteratorAggregate<string>
 */
final readonly class Encoded implements \IteratorAggregate, \Stringable
{
    /**
     * @param \Traversable<string> $chunks
     */
    public function __construct(
        private \Traversable $chunks,
    ) {
    }

    public function getIterator(): \Traversable
    {
        return $this->chunks;
    }

    public function __toString(): string
    {
        $encoded = '';
        foreach ($this->chunks as $chunk) {
            $encoded .= $chunk;
        }

        return $encoded;
    }
}
