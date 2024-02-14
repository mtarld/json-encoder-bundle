<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel;

/**
 * Defines the way to access a scalar value.
 */
final readonly class ScalarDataAccessor implements DataAccessorInterface
{
    public function __construct(
        public mixed $value,
    ) {
    }
}
