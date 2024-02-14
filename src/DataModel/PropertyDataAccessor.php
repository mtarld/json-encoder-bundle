<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel;

/**
 * Defines the way to access data using an object property.
 */
final readonly class PropertyDataAccessor implements DataAccessorInterface
{
    public function __construct(
        public DataAccessorInterface $objectAccessor,
        public string $propertyName,
    ) {
    }
}
