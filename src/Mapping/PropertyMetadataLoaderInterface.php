<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Mapping;

/**
 * Loads properties encoding/decoding metadata for a given $className.
 *
 * This metadata can be used by the DataModelBuilder to create
 * a more appropriate ObjectNode.
 */
interface PropertyMetadataLoaderInterface
{
    /**
     * @param class-string         $className
     * @param array<string, mixed> $config
     * @param array<string, mixed> $context
     *
     * @return array<string, PropertyMetadata>
     */
    public function load(string $className, array $config, array $context): array;
}
