<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel\Decode;

use Mtarld\JsonEncoderBundle\DataModel\DataAccessorInterface;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * Represents an object in the data model graph representation.
 */
final readonly class ObjectNode implements DataModelNodeInterface
{
    /**
     * @param array<string, array{name: string, value: DataModelNodeInterface, accessor: callable(DataAccessorInterface): DataAccessorInterface}> $properties
     */
    public function __construct(
        public ObjectType $type,
        public array $properties,
        public bool $ghost = false,
    ) {
    }

    public static function ghost(ObjectType|UnionType $type): self
    {
        return new self($type, [], true);
    }

    public function getIdentifier(): string
    {
        return (string) $this->type;
    }

    public function getType(): ObjectType
    {
        return $this->type;
    }
}
