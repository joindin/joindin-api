<?php

declare(strict_types=1);

namespace Joindin\Api\Exception;

final class AuthorizationException extends \RuntimeException
{
    private const MESSAGE = 'This operation requires %s privileges.';

    public static function forNonAdministrator(): self
    {
        return new static(sprintf(self::MESSAGE, 'admin'), 403);
    }
}
