<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle;

use Psr\Container\ContainerInterface;
use Mtarld\JsonEncoderBundle\DataModel\Encode\DataModelBuilder;
use Mtarld\JsonEncoderBundle\Encode\EncodeAs;
use Mtarld\JsonEncoderBundle\Encode\EncoderGenerator;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoaderInterface;
use Mtarld\JsonEncoderBundle\Stream\StreamWriterInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * @implements EncoderInterface<array{
 *   type?: Type,
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

    public function encode(mixed $data, array $config = []): \Traversable&\Stringable
    {
        if (null === ($type = $config['type'] ?? null)) {
            $type = \is_object($data) ? Type::object($data::class) : Type::builtin(get_debug_type($data));
        }

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
}
