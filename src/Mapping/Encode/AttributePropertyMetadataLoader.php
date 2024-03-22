<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Mapping\Encode;

use Mtarld\JsonEncoderBundle\Attribute\EncodedName;
use Mtarld\JsonEncoderBundle\Attribute\EncodeFormatter;
use Mtarld\JsonEncoderBundle\Attribute\MaxDepth;
use Mtarld\JsonEncoderBundle\Mapping\PhpDocAwareReflectionTypeResolver;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoaderInterface;

/**
 * Enhances properties encoding metadata based on properties' attributes.
 *
 * @internal
 */
final readonly class AttributePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private PropertyMetadataLoaderInterface $decorated,
        private PhpDocAwareReflectionTypeResolver $typeResolver,
    ) {
    }

    public function load(string $className, array $config, array $context): array
    {
        $initialResult = $this->decorated->load($className, $config, $context);
        $result = [];

        foreach ($initialResult as $initialEncodedName => $initialMetadata) {
            $attributesMetadata = $this->getPropertyAttributesMetadata(new \ReflectionProperty($className, $initialMetadata->name));
            $encodedName = $attributesMetadata['name'] ?? $initialEncodedName;

            if (isset($attributesMetadata['max_depth']) && ($context['depth_counters'][$className] ?? 0) > $attributesMetadata['max_depth']) {
                if (null === $formatter = $attributesMetadata['max_depth_reached_formatter'] ?? null) {
                    continue;
                }

                $reflectionFormatter = new \ReflectionFunction(\Closure::fromCallable($formatter));
                $type = $this->typeResolver->resolve($reflectionFormatter);

                $result[$encodedName] = $initialMetadata
                    ->withType($type)
                    ->withFormatter($formatter(...));

                continue;
            }

            if (null !== $formatter = $attributesMetadata['formatter'] ?? null) {
                $reflectionFormatter = new \ReflectionFunction(\Closure::fromCallable($formatter));
                $type = $this->typeResolver->resolve($reflectionFormatter);

                $result[$encodedName] = $initialMetadata
                    ->withType($type)
                    ->withFormatter($formatter(...));

                continue;
            }

            $result[$encodedName] = $initialMetadata;
        }

        return $result;
    }

    /**
     * @return array{name?: string, formatter?: callable, max_depth?: int, max_depth_reached_formatter?: ?callable}
     */
    private function getPropertyAttributesMetadata(\ReflectionProperty $reflectionProperty): array
    {
        $metadata = [];

        $reflectionAttribute = $reflectionProperty->getAttributes(EncodedName::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['name'] = $reflectionAttribute->newInstance()->name;
        }

        $reflectionAttribute = $reflectionProperty->getAttributes(EncodeFormatter::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['formatter'] = $reflectionAttribute->newInstance()->formatter;
        }

        $reflectionAttribute = $reflectionProperty->getAttributes(MaxDepth::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $attributeInstance = $reflectionAttribute->newInstance();

            $metadata['max_depth'] = $attributeInstance->maxDepth;
            $metadata['max_depth_reached_formatter'] = $attributeInstance->maxDepthReachedFormatter;
        }

        return $metadata;
    }
}
