<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel\Encode;

use Mtarld\JsonEncoderBundle\DataModel\DataAccessorInterface;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * Represents an object in the data model graph representation.
 */
final readonly class ObjectNode implements DataModelNodeInterface
{
    /**
     * @param array<string, DataModelNodeInterface> $properties
     */
    public function __construct(
        public DataAccessorInterface $accessor,
        public ObjectType $type,
        public array $properties,
        public bool $transformed,
    ) {
    }

    public function getType(): ObjectType
    {
        return $this->type;
    }

    public function getAccessor(): DataAccessorInterface
    {
        return $this->accessor;
    }
}
