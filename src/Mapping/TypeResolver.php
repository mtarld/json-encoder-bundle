<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Mapping;

use PHPStan\PhpDocParser\Ast\PhpDoc\InvalidTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ReturnTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolverInterface;

/**
 * Resolves type on reflection priorizing PHP documentation.
 *
 * @internal
 */
final readonly class TypeResolver
{
    private ?PhpDocParser $phpDocParser;
    private ?Lexer $lexer;

    public function __construct(
        private TypeResolverInterface $typeResolver,
        private TypeContextFactory $typeContextFactory,
    ) {
        $this->phpDocParser = class_exists(PhpDocParser::class) ? new PhpDocParser(new TypeParser(), new ConstExprParser()) : null;
        $this->lexer = class_exists(PhpDocParser::class) ? new Lexer() : null;
    }

    public function resolve(\ReflectionProperty|\ReflectionParameter|\ReflectionFunctionAbstract $reflection): Type
    {
        if (!$this->phpDocParser) {
            return $this->typeResolver->resolve($reflection);
        }

        if (!$docComment = $reflection->getDocComment()) {
            return $this->typeResolver->resolve($reflection);
        }

        $typeContext = $this->typeContextFactory->createFromReflection($reflection);

        $tagName = match (true) {
            $reflection instanceof \ReflectionProperty => '@var',
            $reflection instanceof \ReflectionParameter => '@param',
            $reflection instanceof \ReflectionFunctionAbstract => '@return',
        };

        $tokens = new TokenIterator($this->lexer->tokenize($docComment));
        $docNode = $this->phpDocParser->parse($tokens);

        foreach ($docNode->getTagsByName($tagName) as $tag) {
            $tagValue = $tag->value;

            if (
                $tagValue instanceof VarTagValueNode
                || $tagValue instanceof ParamTagValueNode && $tagName && $reflection->getName() === $tagValue->parameterName
                || $tagValue instanceof ReturnTagValueNode
            ) {
                return $this->typeResolver->resolve((string) $tagValue, $typeContext);
            }
        }

        return $this->typeResolver->resolve($reflection);
    }
}
