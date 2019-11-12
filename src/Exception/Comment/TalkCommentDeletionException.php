<?php

declare(strict_types=1);

namespace Joindin\Api\Exception\Comment;

use Teapot\StatusCode\Http;
use RuntimeException;

final class TalkCommentDeletionException extends RuntimeException
{
    public static function forUser(int $userId): self
    {
        return new static(
            sprintf('There was a problem deleting talk comments for user %d', $userId),
            Http::INTERNAL_SERVER_ERROR
        );
    }
}
