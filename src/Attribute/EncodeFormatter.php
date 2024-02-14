<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Attribute;

/**
 * Defines a callable that will be used to format the property data during encoding.
 *
 * The first argument of that callable must be the input data.
 * Then, it is possible to inject the config and services thanks to their FQCN.
 *
 * It must return the formatted data.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class EncodeFormatter
{
    /**
     * @param callable(mixed $value, mixed ...$services): mixed $formatter
     */
    public function __construct(
        public mixed $formatter,
    ) {
    }
}
