<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DependencyInjection;

use Mtarld\JsonEncoderBundle\Attribute\DecodeFormatter;
use Mtarld\JsonEncoderBundle\Attribute\EncodeFormatter;
use Mtarld\JsonEncoderBundle\Attribute\MaxDepth;
use Mtarld\JsonEncoderBundle\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\DependencyInjection\TypedReference;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * Creates and injects a service locator containing encodable classes formatter's needed services.
 *
 * @internal
 */
final readonly class RuntimeServicesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $formatters = [];
        foreach ($container->getDefinitions() as $definition) {
            if (!$definition->hasTag('json_encoder.encodable')) {
                continue;
            }

            $formatters = [
                ...$formatters,
                ...$this->getClassFormatters($container, $definition->getClass()),
            ];
        }

        $runtimeServices = [];
        foreach ($formatters as $formatter) {
            if (null === $formatter->getClosureScopeClass()) {
                continue;
            }

            $formatterName = sprintf('%s::%s', $formatter->getClosureScopeClass()->getName(), $formatter->getName());
            foreach ($this->retrieveServices($container, $formatter) as $serviceName => $reference) {
                $runtimeServices[sprintf('%s[%s]', $formatterName, $serviceName)] = new ServiceClosureArgument($reference);
            }
        }

        $container->register('.json_encoder.runtime_services', ServiceLocator::class)
            ->addArgument($runtimeServices)
            ->addTag('container.service_locator');
    }

    /**
     * @param class-string $className
     *
     * @return list<\ReflectionFunction>
     */
    private function getClassFormatters(ContainerBuilder $container, string $className): array
    {
        if (null === $reflection = $container->getReflectionClass($className)) {
            throw new InvalidArgumentException(sprintf('Class "%s" cannot be found.', $className));
        }

        $formatters = [];
        foreach ($reflection->getProperties() as $property) {
            foreach ($property->getAttributes() as $attribute) {
                if (!\in_array($attribute->getName(), [EncodeFormatter::class, DecodeFormatter::class, MaxDepth::class])) {
                    continue;
                }

                /** @var EncodeFormatter|DecodeFormatter|MaxDepth $attributeInstance */
                $attributeInstance = $attribute->newInstance();

                $formatter = $attributeInstance instanceof EncodeFormatter || $attributeInstance instanceof DecodeFormatter
                    ? $attributeInstance->formatter
                    : $attributeInstance->maxDepthReachedFormatter;

                $formatters[] = new \ReflectionFunction(\Closure::fromCallable($formatter));
            }
        }

        return $formatters;
    }

    /**
     * @return list<Reference>
     */
    private function retrieveServices(ContainerBuilder $container, \ReflectionFunction $function): array
    {
        $services = [];

        foreach ($function->getParameters() as $i => $parameter) {
            // first argument is always the data itself
            if (0 === $i) {
                continue;
            }

            $type = preg_replace('/(^|[(|&])\\\\/', '\1', ltrim(ProxyHelper::exportType($parameter) ?? '', '?'));

            if ($autowireAttribute = ($parameter->getAttributes(Autowire::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null)) {
                $value = $autowireAttribute->newInstance()->value;

                if ($value instanceof Reference) {
                    $services[$parameter->name] = $type
                        ? new TypedReference((string) $value, $type, name: $parameter->name)
                        : $value;

                    continue;
                }

                $services[$parameter->name] = new Reference('.value.'.$container->hash($value));
                $container->register((string) $services[$parameter->name], 'mixed')
                    ->setFactory('current')
                    ->addArgument([$value]);

                continue;
            }

            if ('' === $type) {
                continue;
            }

            if ('array' === $type && 'config' === $parameter->name) {
                continue;
            }

            $services[$parameter->name] = new TypedReference($type, $type, name: Target::parseName($parameter));
        }

        return $services;
    }
}
