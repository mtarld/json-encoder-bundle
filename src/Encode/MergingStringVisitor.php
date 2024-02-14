<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Encode;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

/**
 * Abstraction that merges strings which are written
 * consequently to reduce the call instructions amount.
 *
 * @internal
 */
abstract class MergingStringVisitor extends NodeVisitorAbstract
{
    private string $buffer = '';

    abstract protected function isMergeableNode(Node $node): bool;

    abstract protected function getStringToMerge(Node $node): string;

    abstract protected function getMergedNode(string $merged): Stmt;

    final public function leaveNode(Node $node): int|Node|array|null
    {
        if (!$this->isMergeableNode($node)) {
            return null;
        }

        /** @var Node|null $next */
        $next = $node->getAttribute('next');

        if ($next && $this->isMergeableNode($next)) {
            $this->buffer .= $this->getStringToMerge($node);

            return NodeTraverser::REMOVE_NODE;
        }

        $string = $this->buffer.$this->getStringToMerge($node);
        $this->buffer = '';

        return $this->getMergedNode($string);
    }
}
