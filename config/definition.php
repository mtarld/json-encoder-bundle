<?php

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $definition
        ->rootNode()
            ->info('JSON encoder configuration')
            ->fixXmlConfig('encodable_path')
            ->children()
                ->arrayNode('encodable_paths')
                    ->info('Defines where to find classes to encoded/decoded.')
                    ->beforeNormalization()->ifString()->then(function ($v) { return [$v]; })->end()
                    ->prototype('scalar')->end()
                    ->defaultValue([])
                ->end()
            ->end()
        ->end()
    ;
};
