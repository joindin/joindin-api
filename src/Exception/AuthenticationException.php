<?php

declare(strict_types=1);

namespace Joindin\Api\Exception;

final class AuthenticationException extends \RuntimeException
{
    private const MESSAGE = 'You must be logged in to perform this operation.';

    public static function forUnauthenticatedUser(string $message = null): self
    {
        return new static($message ?? self::MESSAGE, 401);
    }
}
