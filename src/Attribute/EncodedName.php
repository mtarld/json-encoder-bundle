<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Attribute;

/**
 * Defines the encoded property name.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class EncodedName
{
    public function __construct(
        public string $name,
    ) {
    }
}
