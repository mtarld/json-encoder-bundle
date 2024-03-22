<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Mapping\Decode;

use Mtarld\JsonEncoderBundle\Attribute\DecodeFormatter;
use Mtarld\JsonEncoderBundle\Attribute\EncodedName;
use Mtarld\JsonEncoderBundle\Mapping\PhpDocAwareReflectionTypeResolver;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoaderInterface;

/**
 * Enhances properties decoding metadata based on properties' attributes.
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

            if (null !== $formatter = $attributesMetadata['formatter'] ?? null) {
                $reflectionFormatter = new \ReflectionFunction(\Closure::fromCallable($formatter));
                $type = $this->typeResolver->resolve($reflectionFormatter->getParameters()[0]);

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
     * @return array{name?: string, formatter?: callable}
     */
    private function getPropertyAttributesMetadata(\ReflectionProperty $reflectionProperty): array
    {
        $metadata = [];

        $reflectionAttribute = $reflectionProperty->getAttributes(EncodedName::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['name'] = $reflectionAttribute->newInstance()->name;
        }

        $reflectionAttribute = $reflectionProperty->getAttributes(DecodeFormatter::class, \ReflectionAttribute::IS_INSTANCEOF)[0] ?? null;
        if (null !== $reflectionAttribute) {
            $metadata['formatter'] = $reflectionAttribute->newInstance()->formatter;
        }

        return $metadata;
    }
}
