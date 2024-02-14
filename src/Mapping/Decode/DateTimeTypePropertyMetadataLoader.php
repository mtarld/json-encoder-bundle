<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Mapping\Decode;

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
                    ->withFormatter(self::castStringToDateTime(...));
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function castStringToDateTime(string $string, array $config): \DateTimeInterface
    {
        if (false !== $dateTime = \DateTimeImmutable::createFromFormat($config['date_time_format'] ?? \DateTimeInterface::RFC3339, $string)) {
            return $dateTime;
        }

        return new \DateTimeImmutable($string);
    }
}
