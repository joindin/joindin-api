<?php


interface SpamCheckServiceInterface
{
    /**
     * Check your comment against the spam check service.
     *
     * @param array  $data
     * @param string $userIp
     * @param string $userAgent
     *
     * @return bool true if the comment is okay, false if it got rated as spam
     */
    public function isCommentAcceptable(array $data, $userIp, $userAgent);
}
