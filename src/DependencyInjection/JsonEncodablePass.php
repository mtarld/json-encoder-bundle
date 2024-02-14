<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tags classes specified in the `json_encoder.encodable_paths` parameter globs as encodable.
 *
 * @internal
 */
final readonly class JsonEncodablePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('json_encoder.encodable_paths')) {
            return;
        }

        foreach ($this->getEncodableClassNames($container->getParameter('json_encoder.encodable_paths')) as $className) {
            $container->register($className, $className)
                ->setAbstract(true)
                ->addTag('container.excluded')
                ->addTag('json_encoder.encodable');
        }
    }

    /**
     * @param list<string> $globs
     *
     * @return iterable<class-string>
     */
    private function getEncodableClassNames(array $globs): iterable
    {
        $includedFiles = [];

        foreach ($globs as $glob) {
            $paths = glob($glob, (\defined('GLOB_BRACE') ? \GLOB_BRACE : 0) | \GLOB_ONLYDIR | \GLOB_NOSORT);

            foreach ($paths as $path) {
                if (!is_dir($path)) {
                    continue;
                }

                $phpFiles = new \RegexIterator(
                    new \RecursiveIteratorIterator(
                        new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                        \RecursiveIteratorIterator::LEAVES_ONLY
                    ),
                    '/^.+\.php$/i',
                    \RecursiveRegexIterator::GET_MATCH
                );

                foreach ($phpFiles as $file) {
                    $sourceFile = realpath($file[0]);

                    try {
                        require_once $sourceFile;
                    } catch (\Throwable) {
                        continue;
                    }

                    $includedFiles[$sourceFile] = true;
                }
            }

            foreach (get_declared_classes() as $class) {
                $reflectionClass = new \ReflectionClass($class);
                $sourceFile = $reflectionClass->getFileName();

                if (!isset($includedFiles[$sourceFile])) {
                    continue;
                }

                if ($reflectionClass->isAbstract() || $reflectionClass->isInterface() || $reflectionClass->isTrait()) {
                    continue;
                }

                yield $reflectionClass->getName();
            }
        }
    }
}
