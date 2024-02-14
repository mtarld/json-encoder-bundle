<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel;

/**
 * Defines the way to access data using a variable.
 */
final readonly class VariableDataAccessor implements DataAccessorInterface
{
    public function __construct(
        public string $name,
    ) {
    }
}
