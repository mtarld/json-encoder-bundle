<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Decode;

use Mtarld\JsonEncoderBundle\Exception\UnexpectedValueException;

/**
 * Instantiates a new $className eagerly, then set the given properties.
 *
 * The $className class must have a constructor without any parameter
 * and the related properties must be public.
 */
final readonly class Instantiator
{
    public function instantiate(string $className, array $properties): object
    {
        $object = new $className();

        foreach ($properties as $name => $value) {
            try {
                $object->{$name} = $value;
            } catch (\TypeError|UnexpectedValueException $e) {
                throw new UnexpectedValueException($e->getMessage(), previous: $e);
            }
        }

        return $object;
    }
}
