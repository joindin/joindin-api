<?php

namespace Joindin\Api\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception;

/**
 * A class that lets you check against an external service (Akismet)
 * for spam in your content
 */
class SpamCheckService implements SpamCheckServiceInterface
{
    private $httpClient;

    protected $akismetUrl;

    protected $blog;

    /**
     * @param ClientInterface $httpClient
     * @param string $apiKey
     * @param string $blog
     */
    public function __construct(ClientInterface $httpClient, $apiKey, $blog)
    {
        $this->httpClient = $httpClient;
        $this->akismetUrl = 'https://' . $apiKey . '.rest.akismet.com';
        $this->blog = $blog;
    }

    /**
     * Check your comment against the spam check service
     *
     * @see https://akismet.crom/development/api/#comment-check
     *
     * @param string $comment
     * @param string $userIp
     * @param string $userAgent
     *
     * @return bool true if the comment is okay, false if it got rated as spam
     */
    public function isCommentAcceptable(string $comment, $userIp, $userAgent)
    {
        if ('' === trim($comment)) {
            return false;
        }

        try {
            $response = $this->httpClient->request(
                'POST',
                sprintf(
                    '%s/1.1/comment-check',
                    $this->akismetUrl
                ),
                [
                    'body' => http_build_query([
                        'blog' => $this->blog,
                        'comment_content' => $comment,
                        'user_agent' => $userAgent,
                        'user_ip' => $userIp,
                    ]),
                ]
            );
        } catch (Exception\GuzzleException $exception) {
            return false;
        }

        return 'true' === $response->getBody()->getContents();
    }
}
