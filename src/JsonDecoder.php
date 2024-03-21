<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle;

use Mtarld\JsonEncoderBundle\Mapping\Decode\AttributePropertyMetadataLoader;
use Mtarld\JsonEncoderBundle\Mapping\Decode\DateTimeTypePropertyMetadataLoader;
use Mtarld\JsonEncoderBundle\Mapping\GenericTypePropertyMetadataLoader;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoader;
use Psr\Container\ContainerInterface;
use Mtarld\JsonEncoderBundle\DataModel\Decode\DataModelBuilder;
use Mtarld\JsonEncoderBundle\Decode\DecodeFrom;
use Mtarld\JsonEncoderBundle\Decode\DecoderGenerator;
use Mtarld\JsonEncoderBundle\Decode\Instantiator;
use Mtarld\JsonEncoderBundle\Decode\LazyInstantiator;
use Mtarld\JsonEncoderBundle\Mapping\PhpDocAwareReflectionTypeResolver;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoaderInterface;
use Mtarld\JsonEncoderBundle\Stream\BufferedStream;
use Mtarld\JsonEncoderBundle\Stream\StreamReaderInterface;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeContext\TypeContextFactory;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;

/**
 * @implements DecoderInterface<array{
 *   date_time_format?: string,
 *   force_generation?: bool,
 * }>
 */
final readonly class JsonDecoder implements DecoderInterface
{
    private DecoderGenerator $decoderGenerator;
    private Instantiator $instantiator;
    private LazyInstantiator $lazyInstantiator;

    public function __construct(
        PropertyMetadataLoaderInterface $propertyMetadataLoader,
        string $cacheDir,
        private ?ContainerInterface $runtimeServices = null,
    ) {
        $this->decoderGenerator = new DecoderGenerator(new DataModelBuilder($propertyMetadataLoader, $runtimeServices), $cacheDir);
        $this->instantiator = new Instantiator();
        $this->lazyInstantiator = new LazyInstantiator($cacheDir);
    }

    public function decode(StreamReaderInterface|\Traversable|\Stringable|string $input, Type $type, array $config = []): mixed
    {
        if ($input instanceof \Traversable && !$input instanceof StreamReaderInterface) {
            $chunks = $input;
            $input = new BufferedStream();
            foreach ($chunks as $chunk) {
                $input->write($chunk);
            }
        }

        $isStream = $input instanceof StreamReaderInterface;
        $isResourceStream = $isStream && method_exists($input, 'getResource');

        $path = $this->decoderGenerator->generate($type, match (true) {
            $isResourceStream => DecodeFrom::RESOURCE,
            $isStream => DecodeFrom::STREAM,
            default => DecodeFrom::STRING,
        }, $config);

        return (require $path)($isResourceStream ? $input->getResource() : $input, $config, $isStream ? $this->lazyInstantiator : $this->instantiator, $this->runtimeServices);
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
