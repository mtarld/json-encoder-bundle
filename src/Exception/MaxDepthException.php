<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Exception;

final class MaxDepthException extends RuntimeException
{
    /**
     * @param class-string $className
     */
    public function __construct(string $className, int $limit)
    {
        parent::__construct(sprintf('Max depth has been reached for class "%s" (configured limit: %d).', $className, $limit));
    }
}
