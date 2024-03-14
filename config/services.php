<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Mtarld\JsonEncoderBundle\CacheWarmer\EncoderDecoderCacheWarmer;
use Mtarld\JsonEncoderBundle\CacheWarmer\LazyGhostCacheWarmer;
use Mtarld\JsonEncoderBundle\JsonDecoder;
use Mtarld\JsonEncoderBundle\JsonEncoder;
use Mtarld\JsonEncoderBundle\JsonDecoderInterface;
use Mtarld\JsonEncoderBundle\JsonEncoderInterface;
use Mtarld\JsonEncoderBundle\Mapping\Decode\AttributePropertyMetadataLoader as DecodeAttributePropertyMetadataLoader;
use Mtarld\JsonEncoderBundle\Mapping\Decode\DateTimeTypePropertyMetadataLoader as DecodeDateTimeTypePropertyMetadataLoader;
use Mtarld\JsonEncoderBundle\Mapping\Encode\AttributePropertyMetadataLoader as EncodeAttributePropertyMetadataLoader;
use Mtarld\JsonEncoderBundle\Mapping\Encode\DateTimeTypePropertyMetadataLoader as EncodeDateTimeTypePropertyMetadataLoader;
use Mtarld\JsonEncoderBundle\Mapping\GenericTypePropertyMetadataLoader;
use Mtarld\JsonEncoderBundle\Mapping\PropertyMetadataLoader;
use Mtarld\JsonEncoderBundle\Mapping\TypeResolver;

return static function (ContainerConfigurator $container) {
    $container->services()
        // encoder/decoder
        ->set('json_encoder.encoder', JsonEncoder::class)
            ->args([
                service('json_encoder.encode.property_metadata_loader'),
                param('kernel.cache_dir'),
                service('.json_encoder.runtime_services')->nullOnInvalid(),
            ])
        ->set('json_encoder.decoder', JsonDecoder::class)
            ->args([
                service('json_encoder.decode.property_metadata_loader'),
                param('kernel.cache_dir'),
                service('.json_encoder.runtime_services')->nullOnInvalid(),
            ])
        ->alias(JsonEncoderInterface::class, 'json_encoder.encoder')
        ->alias(JsonDecoderInterface::class, 'json_encoder.decoder')

        // metadata
        ->set('.json_encoder.type_resolver', TypeResolver::class)
            ->args([
                service('type_info.resolver'),
                service('type_info.type_context_factory'),
            ])

        ->stack('json_encoder.encode.property_metadata_loader', [
            inline_service(EncodeAttributePropertyMetadataLoader::class)
                ->args([
                    service('.inner'),
                    service('.json_encoder.type_resolver'),
                ]),
            inline_service(EncodeDateTimeTypePropertyMetadataLoader::class)
                ->args([
                    service('.inner'),
                ]),
            inline_service(GenericTypePropertyMetadataLoader::class)
                ->args([
                    service('.inner'),
                    service('type_info.type_context_factory'),
                ]),
            inline_service(PropertyMetadataLoader::class)
                ->args([
                    service('.json_encoder.type_resolver'),
                ]),
        ])

        ->stack('json_encoder.decode.property_metadata_loader', [
            inline_service(DecodeAttributePropertyMetadataLoader::class)
                ->args([
                    service('.inner'),
                    service('.json_encoder.type_resolver'),
                ]),
            inline_service(DecodeDateTimeTypePropertyMetadataLoader::class)
                ->args([
                    service('.inner'),
                ]),
            inline_service(GenericTypePropertyMetadataLoader::class)
                ->args([
                    service('.inner'),
                    service('type_info.type_context_factory'),
                ]),
            inline_service(PropertyMetadataLoader::class)
                ->args([
                    service('.json_encoder.type_resolver'),
                ]),
        ])

        // cache
        ->set('.json_encoder.cache_warmer.encoder_decoder', EncoderDecoderCacheWarmer::class)
            ->args([
                abstract_arg('json encodable types'),
                service('json_encoder.encode.property_metadata_loader'),
                service('json_encoder.decode.property_metadata_loader'),
                param('kernel.cache_dir'),
                service('.json_encoder.runtime_services')->nullOnInvalid(),
                service('logger')->ignoreOnInvalid(),
            ])
            ->tag('kernel.cache_warmer')

        ->set('.json_encoder.cache_warmer.lazy_ghost', LazyGhostCacheWarmer::class)
            ->args([
                abstract_arg('json encodable types'),
                param('kernel.cache_dir'),
            ])
            ->tag('kernel.cache_warmer')
    ;
};
