<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Mapping\Encode;

use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * Casts DateTime properties to string properties.
 *
 * @internal
 */
final readonly class DateTimeTypePropertyMetadataLoader implements PropertyMetadataLoaderInterface
{
    public function __construct(
        private PropertyMetadataLoaderInterface $decorated,
    ) {
    }

    public function load(string $className, array $config, array $context): array
    {
        $result = $this->decorated->load($className, $config, $context);

        foreach ($result as &$metadata) {
            $type = $metadata->type;

            if ($type instanceof ObjectType && is_a($type->getClassName(), \DateTimeInterface::class, true)) {
                $metadata = $metadata
                    ->withType(Type::string())
                    ->withFormatter(self::castDateTimeToString(...));
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function castDateTimeToString(\DateTimeInterface $dateTime, array $config): string
    {
        return $dateTime->format($config['date_time_format'] ?? \DateTimeInterface::RFC3339);
    }
}
