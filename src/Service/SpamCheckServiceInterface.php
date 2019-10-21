<?php

namespace Joindin\Api\Service;

interface SpamCheckServiceInterface
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
    public function isCommentAcceptable(string $comment, $userIp, $userAgent);
}
