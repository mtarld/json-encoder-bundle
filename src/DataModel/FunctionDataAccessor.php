<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel;

/**
 * Defines the way to access data using a function (or a method).
 */
final readonly class FunctionDataAccessor implements DataAccessorInterface
{
    /**
     * @param list<DataAccessorInterface> $arguments
     */
    public function __construct(
        public string $functionName,
        public array $arguments,
        public ?DataAccessorInterface $objectAccessor = null,
    ) {
    }
}
