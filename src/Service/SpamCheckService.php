<?php

namespace Joindin\Api\Service;

/**
 * A class that lets you check against an external service (Akismet)
 * for spam in your content
 */
class SpamCheckService implements SpamCheckServiceInterface
{
    protected $akismetUrl;

    protected $blog;

    /**
     * @param string $apiKey
     * @param string $blog
     */
    public function __construct($apiKey, $blog)
    {
        $this->akismetUrl = 'https://' . $apiKey . '.rest.akismet.com';
        $this->blog       = $blog;
    }

    /**
     * Check your comment against the spam check service
     *
     * @param array  $data
     * @param string $userIp
     * @param string $userAgent
     *
     * @return bool true if the comment is okay, false if it got rated as spam
     */
    public function isCommentAcceptable(array $data, $userIp, $userAgent)
    {
        if (!array_key_exists('comment', $data) || !is_string($data['comment']) || '' === trim($data['comment'])) {
            return false;
        }

        $comment = [];

        // set some required fields
        $comment['blog'] = $this->blog;

        // TODO what are better values to use for these required fields?
        $comment['user_ip']    = $userIp;
        $comment['user_agent'] = $userAgent;

        // now use the incoming data
        $comment['comment_content'] = $data['comment'];

        // actually do the check
        $ch = curl_init($this->akismetUrl . '/1.1/comment-check');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $comment);

        $result = curl_exec($ch);
        curl_close($ch);

        // if the result is false, it wasn't spam and we can return true
        // to indicate that the comment is acceptable
        if ($result == "false") {
            return true;
        }

        // otherwise, anything could have happened and we don't know if it's acceptable
        // TODO log what did happen
        return false;
    }
}
