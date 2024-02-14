<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel\Decode;

use Psr\Container\ContainerInterface;
use Mtarld\JsonEncoderBundle\DataModel\DataAccessorInterface;
use Mtarld\JsonEncoderBundle\DataModel\FunctionDataAccessor;
use Mtarld\JsonEncoderBundle\DataModel\ScalarDataAccessor;
use Mtarld\JsonEncoderBundle\DataModel\VariableDataAccessor;
use Mtarld\JsonEncoderBundle\Exception\LogicException;
use Mtarld\JsonEncoderBundle\Exception\UnsupportedException;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * Builds a decoding graph representation of a given type.
 *
 * @internal
 */
final readonly class DataModelBuilder
{
    public function __construct(
        private PropertyMetadataLoaderInterface $propertyMetadataLoader,
        private ?ContainerInterface $runtimeServices = null,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $context
     */
    public function build(Type $type, array $config, array $context = []): DataModelNodeInterface
    {
        $context['original_type'] ??= $type;

        if ($type instanceof UnionType) {
            return new CompositeNode(array_map(fn (Type $t): DataModelNodeInterface => $this->build($t, $config, $context), $type->getTypes()));
        }

        if ($type instanceof BuiltinType || $type instanceof BackedEnumType) {
            return new ScalarNode($type);
        }

        if ($type instanceof ObjectType && !$type instanceof EnumType) {
            $typeString = (string) $type;
            $className = $type->getClassName();

            if ($context['generated_classes'][$typeString] ??= false) {
                return ObjectNode::ghost($type);
            }

            $propertiesNodes = [];
            $context['generated_classes'][$typeString] = true;

            $propertiesMetadata = $this->propertyMetadataLoader->load($className, $config, $context);

            foreach ($propertiesMetadata as $encodedName => $propertyMetadata) {
                $propertiesNodes[$encodedName] = [
                    'name' => $propertyMetadata->name,
                    'value' => $this->build($propertyMetadata->type, $config, $context),
                    'accessor' => function (DataAccessorInterface $accessor) use ($propertyMetadata): DataAccessorInterface {
                        foreach ($propertyMetadata->formatters as $f) {
                            $reflection = new \ReflectionFunction(\Closure::fromCallable($f));
                            $functionName = null === $reflection->getClosureScopeClass()
                                ? $reflection->getName()
                                : sprintf('%s::%s', $reflection->getClosureScopeClass()->getName(), $reflection->getName());

                            $arguments = [];
                            foreach ($reflection->getParameters() as $i => $parameter) {
                                if (0 === $i) {
                                    $arguments[] = $accessor;

                                    continue;
                                }

                                $parameterType = preg_replace('/^\\\\/', '\1', ltrim(ProxyHelper::exportType($parameter) ?? '', '?'));
                                if ('array' === $parameterType && 'config' === $parameter->name) {
                                    $arguments[] = new VariableDataAccessor('config');

                                    continue;
                                }

                                $argumentName = sprintf('%s[%s]', $functionName, $parameter->name);
                                if ($this->runtimeServices && $this->runtimeServices->has($argumentName)) {
                                    $arguments[] = new FunctionDataAccessor(
                                        'get',
                                        [new ScalarDataAccessor($argumentName)],
                                        new VariableDataAccessor('services'),
                                    );

                                    continue;
                                }

                                throw new LogicException(sprintf('Cannot resolve "%s" argument of "%s()".', $parameter->name, $functionName));
                            }

                            $accessor = new FunctionDataAccessor($functionName, $arguments);
                        }

                        return $accessor;
                    },
                ];
            }

            return new ObjectNode($type, $propertiesNodes);
        }

        if ($type instanceof CollectionType) {
            return new CollectionNode($type, $this->build($type->getCollectionValueType(), $config, $context));
        }

        throw new UnsupportedException(sprintf('"%s" type is not supported.', (string) $type));
    }
}
