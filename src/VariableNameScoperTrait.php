<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle;

/**
 * @internal
 */
trait VariableNameScoperTrait
{
    /**
     * @param array{variable_counters?: array<string, int>}&array<string, mixed> $context
     */
    private function scopeVariableName(string $variableName, array &$context): string
    {
        if (!isset($context['variable_counters'][$variableName])) {
            $context['variable_counters'][$variableName] = 0;
        }

        $name = sprintf('%s_%d', $variableName, $context['variable_counters'][$variableName]);

        ++$context['variable_counters'][$variableName];

        return $name;
    }
}
