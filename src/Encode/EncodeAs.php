<?php

declare(strict_types=1);

namespace Mtarld\JsonEncoderBundle\Encode;

/**
 * @internal
 */
enum EncodeAs: string
{
    case STRING = 'string';
    case STREAM = 'stream';
    case RESOURCE = 'resource';
}
