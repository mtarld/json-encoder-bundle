<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\CacheWarmer;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Mtarld\JsonEncoderBundle\DataModel\Decode\DataModelBuilder as DecodeDataModelBuilder;
use Mtarld\JsonEncoderBundle\DataModel\Encode\DataModelBuilder as EncodeDataModelBuilder;
use Mtarld\JsonEncoderBundle\Decode\DecodeFrom;
use Mtarld\JsonEncoderBundle\Decode\DecoderGenerator;
use Mtarld\JsonEncoderBundle\Encode\EncodeAs;
use Mtarld\JsonEncoderBundle\Encode\EncoderGenerator;
use Mtarld\JsonEncoderBundle\Exception\ExceptionInterface;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoaderInterface;
use Symfony\Component\TypeInfo\Type;

/**
 * Generates encoders and decoders PHP files.
 *
 * @internal
 */
final readonly class EncoderDecoderCacheWarmer implements CacheWarmerInterface
{
    private EncoderGenerator $encoderGenerator;
    private DecoderGenerator $decoderGenerator;

    /**
     * @param list<class-string> $encodableClassNames
     */
    public function __construct(
        private array $encodableClassNames,
        PropertyMetadataLoaderInterface $encodePropertyMetadataLoader,
        PropertyMetadataLoaderInterface $decodePropertyMetadataLoader,
        string $cacheDir,
        ?ContainerInterface $runtimeServices = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {
        $this->encoderGenerator = new EncoderGenerator(new EncodeDataModelBuilder($encodePropertyMetadataLoader, $runtimeServices), $cacheDir);
        $this->decoderGenerator = new DecoderGenerator(new DecodeDataModelBuilder($decodePropertyMetadataLoader, $runtimeServices), $cacheDir);
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        foreach ($this->encodableClassNames as $className) {
            $type = Type::object($className);

            $this->warmUpEncoders($type);
            $this->warmUpDecoders($type);
        }

        return [];
    }

    public function isOptional(): bool
    {
        return true;
    }

    private function warmUpEncoders(Type $type): void
    {
        foreach (EncodeAs::cases() as $encodeAs) {
            try {
                $this->encoderGenerator->generate($type, $encodeAs);
            } catch (ExceptionInterface $e) {
                $this->logger->debug('Cannot generate "json" {encodeAs} encoder for "{type}": {exception}', ['type' => (string) $type, 'encodeAs' => $encodeAs, 'exception' => $e]);
            }
        }
    }

    private function warmUpDecoders(Type $type): void
    {
        foreach (DecodeFrom::cases() as $decodeFrom) {
            try {
                $this->decoderGenerator->generate($type, $decodeFrom);
            } catch (ExceptionInterface $e) {
                $this->logger->debug('Cannot generate "json" {decodeFrom} decoder for "{type}": {exception}', ['type' => (string) $type, 'decodeFrom' => $decodeFrom, 'exception' => $e]);
            }
        }
    }
}
