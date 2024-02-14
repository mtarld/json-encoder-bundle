<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Exception;

final class InvalidStreamException extends UnexpectedValueException
{
    public function __construct()
    {
        parent::__construct('Stream is not valid.');
    }
}
