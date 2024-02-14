<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Encode;

use Mtarld\JsonEncoderBundle\DataModel\Encode\DataModelBuilder;
use Mtarld\JsonEncoderBundle\DataModel\VariableDataAccessor;
use Mtarld\JsonEncoderBundle\Exception\RuntimeException;
use Mtarld\JsonEncoderBundle\PhpPrinter;
use PhpParser\PhpVersion;
use Symfony\Component\TypeInfo\Type;

/**
 * Generates and write encoders PHP files.
 *
 * @internal
 */
final readonly class EncoderGenerator
{
    private PhpAstBuilder $phpAstBuilder;
    private PhpOptimizer $phpOptimizer;
    private PhpPrinter $phpPrinter;
    private string $encoderCacheDir;

    public function __construct(
        private DataModelBuilder $dataModelBuilder,
        string $cacheDir,
    ) {
        $this->phpAstBuilder = new PhpAstBuilder();
        $this->phpOptimizer = new PhpOptimizer();
        $this->phpPrinter = class_exists(PhpVersion::class) ? new PhpPrinter(['phpVersion' => PhpVersion::fromComponents(8, 1)]) : new PhpPrinter();
        $this->encoderCacheDir = $cacheDir.'/json_encoder/encoder';
    }

    /**
     * Generates and writes an encoder PHP file and return its path.
     *
     * @param array<string, mixed> $config
     */
    public function generate(Type $type, EncodeAs $encodeAs, array $config = []): string
    {
        $path = $this->getPath($type, $encodeAs);
        if (file_exists($path) && !($config['force_generation'] ?? false)) {
            return $path;
        }

        $dataModel = $this->dataModelBuilder->build($type, new VariableDataAccessor('data'), $config);

        $nodes = $this->phpAstBuilder->build($dataModel, $encodeAs, $config);
        $nodes = $this->phpOptimizer->optimize($nodes);

        $content = $this->phpPrinter->prettyPrintFile($nodes)."\n";

        if (!file_exists($this->encoderCacheDir)) {
            mkdir($this->encoderCacheDir, recursive: true);
        }

        $tmpFile = @tempnam(\dirname($path), basename($path));
        if (false === @file_put_contents($tmpFile, $content)) {
            throw new RuntimeException(sprintf('Failed to write "%s" encoder file.', $path));
        }

        @rename($tmpFile, $path);
        @chmod($path, 0666 & ~umask());

        return $path;
    }

    private function getPath(Type $type, EncodeAs $encodeAs): string
    {
        return sprintf('%s%s%s.json.%s.php', $this->encoderCacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) $type), $encodeAs->value);
    }
}
