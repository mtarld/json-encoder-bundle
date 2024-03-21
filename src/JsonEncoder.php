<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle;

use Mtarld\JsonEncoderBundle\Mapping\Encode\AttributePropertyMetadataLoader;
use Mtarld\JsonEncoderBundle\Mapping\Encode\DateTimeTypePropertyMetadataLoader;
use Mtarld\JsonEncoderBundle\Mapping\GenericTypePropertyMetadataLoader;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoader;
use Psr\Container\ContainerInterface;
use Mtarld\JsonEncoderBundle\DataModel\Encode\DataModelBuilder;
use Mtarld\JsonEncoderBundle\Encode\EncodeAs;
use Mtarld\JsonEncoderBundle\Encode\EncoderGenerator;
use Mtarld\JsonEncoderBundle\Mapping\PhpDocAwareReflectionTypeResolver;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoaderInterface;
use Mtarld\JsonEncoderBundle\Stream\StreamWriterInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * @implements EncoderInterface<array{
 *   stream?: StreamWriterInterface,
 *   max_depth?: int,
 *   date_time_format?: string,
 *   force_generation?: bool,
 * }>
 */
final readonly class JsonEncoder implements EncoderInterface
{
    private EncoderGenerator $encoderGenerator;

    public function __construct(
        private PropertyMetadataLoaderInterface $propertyMetadataLoader,
        string $cacheDir,
        private ?ContainerInterface $runtimeServices = null,
    ) {
        $this->encoderGenerator = new EncoderGenerator(new DataModelBuilder($propertyMetadataLoader, $runtimeServices), $cacheDir);
    }

    public function encode(mixed $data, Type $type, array $config = []): \Traversable&\Stringable
    {
        $stream = $config['stream'] ?? null;
        if (null !== $stream && method_exists($stream, 'getResource')) {
            $stream = $stream->getResource();
        }

        $path = $this->encoderGenerator->generate($type, match (true) {
            $stream instanceof StreamWriterInterface => EncodeAs::STREAM,
            null !== $stream => EncodeAs::RESOURCE,
            default => EncodeAs::STRING,
        }, $config);

        if (null !== $stream) {
            (require $path)($data, $stream, $config, $this->runtimeServices);

            return new Encoded(new \EmptyIterator());
        }

        return new Encoded((require $path)($data, $config, $this->runtimeServices));
    }

    public static function create(?string $cacheDir = null, ?ContainerInterface $runtimeServices = null): static
    {
        $cacheDir ??= sys_get_temp_dir() . '/json_encoder';

        try {
            $stringTypeResolver = new StringTypeResolver();
        } catch (\Throwable) {
        }

        $typeContextFactory = new TypeContextFactory($stringTypeResolver ?? null);
        $typeResolver = new PhpDocAwareReflectionTypeResolver(TypeResolver::create(), $typeContextFactory);

        return new static(new GenericTypePropertyMetadataLoader(
            new DateTimeTypePropertyMetadataLoader(
                new AttributePropertyMetadataLoader(
                    new PropertyMetadataLoader($typeResolver),
                    $typeResolver,
                ),
            ),
            $typeContextFactory,
        ), $cacheDir, $runtimeServices);
    }
}
