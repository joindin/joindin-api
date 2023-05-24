<?php

namespace Joindin\Api\Service;

class NullSpamCheckService implements SpamCheckServiceInterface
{
    /**
     * Check your comment against the spam check service
     *
     * @param string $comment
     * @param string $userIp
     * @param string $userAgent
     *
     * @return bool true if the comment is okay, false if it got rated as spam
     */
    public function isCommentAcceptable(string $comment, string $userIp, string $userAgent): bool
    {
        return true;
    }
}
