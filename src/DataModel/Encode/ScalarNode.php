<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel\Encode;

use Mtarld\JsonEncoderBundle\DataModel\DataAccessorInterface;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\BuiltinType;

/**
 * Represents a scalar in the data model graph representation.
 *
 * Scalars are the leaves of the data model tree.
 */
final readonly class ScalarNode implements DataModelNodeInterface
{
    public function __construct(
        public DataAccessorInterface $accessor,
        public BuiltinType|BackedEnumType $type,
    ) {
    }

    public function getType(): BuiltinType|BackedEnumType
    {
        return $this->type;
    }

    public function getAccessor(): DataAccessorInterface
    {
        return $this->accessor;
    }
}
