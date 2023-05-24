<?php

declare(strict_types=1);

namespace Joindin\Api\Exception;

use Teapot\StatusCode\Http;

final class AuthorizationException extends \RuntimeException
{
    private const MESSAGE = 'This operation requires %s privileges.';

    public static function forNonAdministrator(): self
    {
        return new self(sprintf(self::MESSAGE, 'admin'), Http::FORBIDDEN);
    }
}
