<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Encode;

use PhpParser\Node;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;

/**
 * Merges strings that are yielded consequently.
 *
 * @internal
 */
final class MergingStringYieldVisitor extends MergingStringVisitor
{
    protected function isMergeableNode(Node $node): bool
    {
        return $node instanceof Expression
            && $node->expr instanceof Yield_
            && $node->expr->value instanceof String_;
    }

    protected function getStringToMerge(Node $node): string
    {
        return $node->expr->value->value;
    }

    protected function getMergedNode(string $merged): Stmt
    {
        return new Expression(new Yield_(new String_($merged)));
    }
}
