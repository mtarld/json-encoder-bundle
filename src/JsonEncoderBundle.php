<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle;

use Mtarld\JsonEncoderBundle\DependencyInjection\JsonEncodablePass;
use Mtarld\JsonEncoderBundle\DependencyInjection\JsonEncoderPass;
use Mtarld\JsonEncoderBundle\DependencyInjection\RuntimeServicesPass;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class JsonEncoderBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new JsonEncodablePass());
        $container->addCompilerPass(new JsonEncoderPass());
        $container->addCompilerPass(new RuntimeServicesPass());
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
    }
}
