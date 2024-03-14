<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Mapping;

/**
 * Loads basic properties encoding/decoding metadata for a given $className.
 *
 * @internal
 */
final readonly class PropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private TypeResolver $typeResolver,
    ) {
    }

    public function load(string $className, array $config, array $context): array
    {
        $result = [];

        foreach ((new \ReflectionClass($className))->getProperties() as $reflectionProperty) {
            if (!$reflectionProperty->isPublic()) {
                continue;
            }

            $name = $encodedName = $reflectionProperty->getName();
            $type = $this->typeResolver->resolve($reflectionProperty);

            $result[$encodedName] = new PropertyMetadata($name, $type);
        }

        return $result;
    }
}
