<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle;

use Psr\Container\ContainerInterface;
use Mtarld\JsonEncoderBundle\DataModel\Decode\DataModelBuilder;
use Mtarld\JsonEncoderBundle\Decode\DecodeFrom;
use Mtarld\JsonEncoderBundle\Decode\DecoderGenerator;
use Mtarld\JsonEncoderBundle\Decode\Instantiator;
use Mtarld\JsonEncoderBundle\Decode\LazyInstantiator;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoaderInterface;
use Mtarld\JsonEncoderBundle\Stream\BufferedStream;
use Mtarld\JsonEncoderBundle\Stream\StreamReaderInterface;
use Symfony\Component\TypeInfo\Type;

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
}
