<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Decode;

use PhpParser\PhpVersion;
use Mtarld\JsonEncoderBundle\DataModel\Decode\DataModelBuilder;
use Mtarld\JsonEncoderBundle\Exception\RuntimeException;
use Mtarld\JsonEncoderBundle\PhpPrinter;
use Symfony\Component\TypeInfo\Type;

/**
 * Generates and writes decoders PHP files.
 *
 * @internal
 */
final readonly class DecoderGenerator
{
    private PhpAstBuilder $phpAstBuilder;
    private PhpPrinter $phpPrinter;
    private string $decoderCacheDir;

    public function __construct(
        private DataModelBuilder $dataModelBuilder,
        string $cacheDir,
    ) {
        $this->phpAstBuilder = new PhpAstBuilder();
        $this->phpPrinter = class_exists(PhpVersion::class) ? new PhpPrinter(['phpVersion' => PhpVersion::fromComponents(8, 1)]) : new PhpPrinter();
        $this->decoderCacheDir = $cacheDir.'/json_encoder/decoder';
    }

    /**
     * Generates and writes a decoder PHP file and return its path.
     *
     * @param array<string, mixed> $config
     */
    public function generate(Type $type, DecodeFrom $decodeFrom, array $config = []): string
    {
        $path = $this->getPath($type, $decodeFrom);
        if (file_exists($path) && !($config['force_generation'] ?? false)) {
            return $path;
        }

        $dataModel = $this->dataModelBuilder->build($type, $config);
        $nodes = $this->phpAstBuilder->build($dataModel, $decodeFrom, $config);
        $content = $this->phpPrinter->prettyPrintFile($nodes)."\n";

        if (!file_exists($this->decoderCacheDir)) {
            mkdir($this->decoderCacheDir, recursive: true);
        }

        $tmpFile = @tempnam(\dirname($path), basename($path));
        if (false === @file_put_contents($tmpFile, $content)) {
            throw new RuntimeException(sprintf('Failed to write "%s" decoder file.', $path));
        }

        @rename($tmpFile, $path);
        @chmod($path, 0666 & ~umask());

        return $path;
    }

    private function getPath(Type $type, DecodeFrom $decodeFrom): string
    {
        return sprintf('%s%s%s.json.%s.php', $this->decoderCacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', (string) $type), $decodeFrom->value);
    }
}
