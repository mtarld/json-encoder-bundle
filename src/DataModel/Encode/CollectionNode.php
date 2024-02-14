<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel\Encode;

use Mtarld\JsonEncoderBundle\DataModel\DataAccessorInterface;
use Symfony\Component\TypeInfo\Type\CollectionType;

/**
 * Represents a collection in the data model graph representation.
 */
final readonly class CollectionNode implements DataModelNodeInterface
{
    public bool $transformed;

    public function __construct(
        public DataAccessorInterface $accessor,
        public CollectionType $type,
        public DataModelNodeInterface $item,
    ) {
    }

    public function getType(): CollectionType
    {
        return $this->type;
    }

    public function getAccessor(): DataAccessorInterface
    {
        return $this->accessor;
    }
}
