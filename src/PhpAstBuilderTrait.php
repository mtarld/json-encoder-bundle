<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle;

use PhpParser\BuilderFactory;
use PhpParser\Node\Expr;
use Mtarld\JsonEncoderBundle\DataModel\DataAccessorInterface;
use Mtarld\JsonEncoderBundle\DataModel\FunctionDataAccessor;
use Mtarld\JsonEncoderBundle\DataModel\PhpExprDataAccessor;
use Mtarld\JsonEncoderBundle\DataModel\PropertyDataAccessor;
use Mtarld\JsonEncoderBundle\DataModel\ScalarDataAccessor;
use Mtarld\JsonEncoderBundle\DataModel\VariableDataAccessor;
use Mtarld\JsonEncoderBundle\Exception\InvalidArgumentException;

/**
 * @internal
 */
trait PhpAstBuilderTrait
{
    use VariableNameScoperTrait;

    private readonly BuilderFactory $builder;

    private function convertDataAccessorToPhpExpr(DataAccessorInterface $accessor): Expr
    {
        if ($accessor instanceof ScalarDataAccessor) {
            return $this->builder->val($accessor->value);
        }

        if ($accessor instanceof VariableDataAccessor) {
            return $this->builder->var($accessor->name);
        }

        if ($accessor instanceof PropertyDataAccessor) {
            return $this->builder->propertyFetch(
                $this->convertDataAccessorToPhpExpr($accessor->objectAccessor),
                $accessor->propertyName,
            );
        }

        if ($accessor instanceof FunctionDataAccessor) {
            $arguments = array_map($this->convertDataAccessorToPhpExpr(...), $accessor->arguments);

            if (null === $accessor->objectAccessor) {
                return $this->builder->funcCall($accessor->functionName, $arguments);
            }

            return $this->builder->methodCall(
                $this->convertDataAccessorToPhpExpr($accessor->objectAccessor),
                $accessor->functionName,
                $arguments,
            );
        }

        if ($accessor instanceof PhpExprDataAccessor) {
            return $accessor->php;
        }

        throw new InvalidArgumentException(sprintf('"%s" cannot be converted to PHP node.', $accessor::class));
    }
}
