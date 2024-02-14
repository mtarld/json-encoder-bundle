<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Mtarld\JsonEncoderBundle\DecoderInterface;
use Mtarld\JsonEncoderBundle\EncoderInterface;

/**
 * Injects encodable classes into services and registers aliases.
 *
 * @internal
 */
final readonly class JsonEncoderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('json_encoder.encoder')) {
            return;
        }

        $encodableClassNames = array_map(
            fn (string $id) => $container->getDefinition($id)->getClass(),
            array_keys($container->findTaggedServiceIds('json_encoder.encodable')),
        );

        $container->getDefinition('.json_encoder.cache_warmer.encoder_decoder')
            ->replaceArgument(0, $encodableClassNames);

        $container->getDefinition('.json_encoder.cache_warmer.lazy_ghost')
            ->replaceArgument(0, $encodableClassNames);

        $container->registerAliasForArgument('json_encoder.encoder', EncoderInterface::class, 'json.encoder');
        $container->registerAliasForArgument('json_encoder.decoder', DecoderInterface::class, 'json.decoder');
    }
}
