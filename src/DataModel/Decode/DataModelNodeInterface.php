<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel\Decode;

use Symfony\Component\TypeInfo\Type;

/**
 * Represents a node in the decoding data model graph representation.
 */
interface DataModelNodeInterface
{
    public function getIdentifier(): string;

    public function getType(): Type;
}
