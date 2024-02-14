<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel\Encode;

use Mtarld\JsonEncoderBundle\DataModel\DataAccessorInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * Represents a node in the encoding data model graph representation.
 */
interface DataModelNodeInterface
{
    public function getType(): Type;

    public function getAccessor(): DataAccessorInterface;
}
