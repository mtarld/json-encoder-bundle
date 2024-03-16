<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle;

use Symfony\Component\TypeInfo\Type;

/**
 * Encodes $data into a specific format according to a $config.
 *
 * @template T of array<string, mixed>
 */
interface EncoderInterface
{
    /**
     * @param T $config
     *
     * @return \Traversable<string>&\Stringable
     */
    public function encode(mixed $data, Type $type, array $config = []): \Traversable&\Stringable;
}
