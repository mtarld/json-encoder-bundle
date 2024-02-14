<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel\Encode;

use Mtarld\JsonEncoderBundle\DataModel\DataAccessorInterface;
use Mtarld\JsonEncoderBundle\Exception\InvalidArgumentException;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\UnionType;

/**
 * Represents a "OR" node composition in the data model graph representation.
 *
 * Composing nodes are sorted by their precision (descending).
 */
final readonly class CompositeNode implements DataModelNodeInterface
{
    private const NODE_PRECISION = [
        CollectionNode::class => 2,
        ObjectNode::class => 1,
        ScalarNode::class => 0,
    ];

    /**
     * @var list<DataModelNodeInterface>
     */
    public array $nodes;

    /**
     * @param list<DataModelNodeInterface> $nodes
     */
    public function __construct(
        public DataAccessorInterface $accessor,
        array $nodes,
    ) {
        if (\count($nodes) < 2) {
            throw new InvalidArgumentException(sprintf('"%s" expects at least 2 nodes.', self::class));
        }

        foreach ($nodes as $n) {
            if ($n instanceof self) {
                throw new InvalidArgumentException(sprintf('Cannot set "%s" as a "%1$s" node.', self::class));
            }
        }

        usort($nodes, fn (CollectionNode|ObjectNode|ScalarNode $a, CollectionNode|ObjectNode|ScalarNode $b): int => self::NODE_PRECISION[$b::class] <=> self::NODE_PRECISION[$a::class]);
        $this->nodes = $nodes;
    }

    public function getType(): UnionType
    {
        return Type::union(...array_map(fn (DataModelNodeInterface $n): Type => $n->getType(), $this->nodes));
    }

    public function getAccessor(): DataAccessorInterface
    {
        return $this->accessor;
    }
}
