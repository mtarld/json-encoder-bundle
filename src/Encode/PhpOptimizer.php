<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Encode;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NodeConnectingVisitor;

/**
 * Optimizes a PHP syntax tree.
 *
 * @internal
 */
final readonly class PhpOptimizer
{
    /**
     * @param list<Node> $nodes
     *
     * @return list<Node>
     */
    public function optimize(array $nodes): array
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NodeConnectingVisitor());
        $nodes = $traverser->traverse($nodes);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new MergingStringYieldVisitor());
        $traverser->addVisitor(new MergingStringStreamWriteVisitor());
        $traverser->addVisitor(new MergingStringFwriteVisitor());

        return $traverser->traverse($nodes);
    }
}
