<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle;

use Mtarld\JsonEncoderBundle\Stream\StreamReaderInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * Decodes an $input into a given $type according to a $config.
 *
 * @template T of array<string, mixed>
 */
interface DecoderInterface
{
    /**
     * @param StreamReaderInterface|\Traversable<string>|\Stringable|string $input
     * @param T                                                             $config
     */
    public function decode(StreamReaderInterface|\Traversable|\Stringable|string $input, Type $type, array $config = []): mixed;
}
