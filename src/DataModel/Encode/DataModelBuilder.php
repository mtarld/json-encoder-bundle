<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DataModel\Encode;

use Psr\Container\ContainerInterface;
use Mtarld\JsonEncoderBundle\DataModel\DataAccessorInterface;
use Mtarld\JsonEncoderBundle\DataModel\FunctionDataAccessor;
use Mtarld\JsonEncoderBundle\DataModel\PropertyDataAccessor;
use Mtarld\JsonEncoderBundle\DataModel\ScalarDataAccessor;
use Mtarld\JsonEncoderBundle\DataModel\VariableDataAccessor;
use Mtarld\JsonEncoderBundle\Exception\LogicException;
use Mtarld\JsonEncoderBundle\Exception\MaxDepthException;
use Mtarld\JsonEncoderBundle\Exception\UnsupportedException;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadata;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoaderInterface;
use Mtarld\JsonEncoderBundle\VariableNameScoperTrait;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\BackedEnumType;
use Symfony\Component\TypeInfo\Type\BuiltinType;
use Symfony\Component\TypeInfo\Type\CollectionType;
use Symfony\Component\TypeInfo\Type\EnumType;
use Symfony\Component\TypeInfo\Type\ObjectType;
use Symfony\Component\TypeInfo\Type\UnionType;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * Builds a encoding graph representation of a given type.
 */
final readonly class DataModelBuilder
{
    use VariableNameScoperTrait;

    public function __construct(
        private PropertyMetadataLoaderInterface $propertyMetadataLoader,
        private ?ContainerInterface $runtimeServices = null,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $context
     */
    public function build(Type $type, DataAccessorInterface $accessor, array $config, array $context = []): DataModelNodeInterface
    {
        $context['original_type'] ??= $type;

        if ($type instanceof UnionType) {
            return new CompositeNode($accessor, array_map(fn (Type $t): DataModelNodeInterface => $this->build($t, $accessor, $config, $context), $type->getTypes()));
        }

        if ($type instanceof BuiltinType || $type instanceof BackedEnumType) {
            return new ScalarNode($accessor, $type);
        }

        if ($type instanceof ObjectType && !$type instanceof EnumType) {
            $transformed = false;
            $className = $type->getClassName();

            $context['depth_counters'][$className] ??= 0;
            ++$context['depth_counters'][$className];

            $maxDepth = $config['max_depth'] ?? 32;
            if ($context['depth_counters'][$className] > $maxDepth) {
                throw new MaxDepthException($className, $maxDepth);
            }

            $propertiesMetadata = $this->propertyMetadataLoader->load($className, $config, ['original_type' => $type] + $context);

            if (\count((new \ReflectionClass($className))->getProperties()) !== \count($propertiesMetadata)
                || array_values(array_map(fn (PropertyMetadata $m): string => $m->name, $propertiesMetadata)) !== array_keys($propertiesMetadata)
            ) {
                $transformed = true;
            }

            $propertiesNodes = [];

            foreach ($propertiesMetadata as $encodedName => $propertyMetadata) {
                $propertyAccessor = new PropertyDataAccessor($accessor, $propertyMetadata->name);

                foreach ($propertyMetadata->formatters as $f) {
                    $transformed = true;
                    $reflection = new \ReflectionFunction(\Closure::fromCallable($f));
                    $functionName = null === $reflection->getClosureScopeClass()
                        ? $reflection->getName()
                        : sprintf('%s::%s', $reflection->getClosureScopeClass()->getName(), $reflection->getName());

                    $arguments = [];
                    foreach ($reflection->getParameters() as $i => $parameter) {
                        if (0 === $i) {
                            $arguments[] = $propertyAccessor;

                            continue;
                        }

                        $parameterType = preg_replace('/(^|[(|&])\\\\/', '\1', ltrim(ProxyHelper::exportType($parameter) ?? '', '?'));
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

                    $propertyAccessor = new FunctionDataAccessor($functionName, $arguments);
                }

                $propertiesNodes[$encodedName] = $this->build($propertyMetadata->type, $propertyAccessor, $config, $context);
            }

            return new ObjectNode($accessor, $type, $propertiesNodes, $transformed);
        }

        if ($type instanceof CollectionType) {
            return new CollectionNode(
                $accessor,
                $type,
                $this->build($type->getCollectionValueType(), new VariableDataAccessor($this->scopeVariableName('value', $context)), $config, $context),
            );
        }

        throw new UnsupportedException(sprintf('"%s" type is not supported.', (string) $type));
    }
}
