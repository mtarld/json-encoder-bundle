<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel;

use PhpParser\Node\Expr;

/**
 * Defines the way to access data using PHP AST.
 */
final readonly class PhpExprDataAccessor implements DataAccessorInterface
{
    public function __construct(
        public Expr $php,
    ) {
    }
}
