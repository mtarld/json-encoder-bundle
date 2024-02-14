<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Decode;

/**
 * @internal
 */
enum DecodeFrom: string
{
    case STRING = 'string';
    case STREAM = 'stream';
    case RESOURCE = 'resource';
}
