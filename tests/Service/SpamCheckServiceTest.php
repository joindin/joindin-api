<?php

declare(strict_types=1);

namespace Joindin\Api\Test\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception;
use GuzzleHttp\Psr7;
use Joindin\Api\Service\SpamCheckService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Teapot\StatusCode\Http;

/**
 * @covers \Joindin\Api\Service\SpamCheckService
 */
final class SpamCheckServiceTest extends TestCase
{

    /**
     * @dataProvider provideBlankOrEmptyString
     */
    public function testIsCommentAcceptableReturnsFalseWhenCommentIsBlankOrEmptyString(string $comment): void
    {
        $apiKey = 'hello123';
        $blog = 'foo-bar-baz';

        $userIp = '123.456.789.012';
        $userAgent = 'Foo/9000';

        $service = new SpamCheckService(
            $this->prophesize(ClientInterface::class)->reveal(),
            $apiKey,
            $blog
        );

        $isCommentAcceptable = $service->isCommentAcceptable(
            $comment,
            $userIp,
            $userAgent
        );

        self::assertFalse($isCommentAcceptable);
    }

    public function provideBlankOrEmptyString(): array
    {
        return [
            'blank' => [' '],
            'empty' => [''],
        ];
    }

    public function testIsCommentAcceptableReturnsFalseWhenExceptionIsThrownDuringRequest(): void
    {
        $comment = <<<TXT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna. 

Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute 
irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat 
cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
TXT;

        $apiKey = 'hello123';
        $blog = 'foo-bar-baz';

        $userIp = '123.456.789.012';
        $userAgent = 'Foo/9000';

        $httpClient = $this->prophesize(ClientInterface::class);

        $httpClient
            ->request(
                Argument::is('POST'),
                Argument::is(sprintf(
                    'https://%s.rest.akismet.com/1.1/comment-check',
                    $apiKey
                )),
                Argument::is([
                    'body' => http_build_query([
                        'blog' => $blog,
                        'comment_content' => $comment,
                        'user_agent' => $userAgent,
                        'user_ip' => $userIp,
                    ]),
                ])
            )
            ->shouldBeCalled()
            ->willThrow(new class extends \RuntimeException implements Exception\GuzzleException {
            });

        $service = new SpamCheckService(
            $httpClient->reveal(),
            $apiKey,
            $blog
        );

        $isCommentAcceptable = $service->isCommentAcceptable(
            $comment,
            $userIp,
            $userAgent
        );

        self::assertFalse($isCommentAcceptable);
    }

    public function testIsCommentAcceptableReturnsFalseWhenResponseBodyEqualsInvalid(): void
    {
        $comment = <<<TXT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna. 

Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute 
irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat 
cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
TXT;

        $apiKey = 'hello123';
        $blog = 'foo-bar-baz';

        $userIp = '123.456.789.012';
        $userAgent = 'Foo/9000';

        $httpClient = $this->prophesize(ClientInterface::class);

        $httpClient
            ->request(
                Argument::is('POST'),
                Argument::is(sprintf(
                    'https://%s.rest.akismet.com/1.1/comment-check',
                    $apiKey
                )),
                Argument::is([
                    'body' => http_build_query([
                        'blog' => $blog,
                        'comment_content' => $comment,
                        'user_agent' => $userAgent,
                        'user_ip' => $userIp,
                    ]),
                ])
            )
            ->shouldBeCalled()
            ->willReturn(new Psr7\Response(
                Http::OK,
                [],
                'invalid'
            ));

        $service = new SpamCheckService(
            $httpClient->reveal(),
            $apiKey,
            $blog
        );

        $isCommentAcceptable = $service->isCommentAcceptable(
            $comment,
            $userIp,
            $userAgent
        );

        self::assertFalse($isCommentAcceptable);
    }

    public function testIsCommentAcceptableReturnsFalseWhenResponseBodyEqualsFalse(): void
    {
        $comment = <<<TXT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna. 

Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute 
irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat 
cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
TXT;

        $apiKey = 'hello123';
        $blog = 'foo-bar-baz';

        $userIp = '123.456.789.012';
        $userAgent = 'Foo/9000';

        $httpClient = $this->prophesize(ClientInterface::class);

        $httpClient
            ->request(
                Argument::is('POST'),
                Argument::is(sprintf(
                    'https://%s.rest.akismet.com/1.1/comment-check',
                    $apiKey
                )),
                Argument::is([
                    'body' => http_build_query([
                        'blog' => $blog,
                        'comment_content' => $comment,
                        'user_agent' => $userAgent,
                        'user_ip' => $userIp,
                    ]),
                ])
            )
            ->shouldBeCalled()
            ->willReturn(new Psr7\Response(
                Http::OK,
                [],
                'false'
            ));

        $service = new SpamCheckService(
            $httpClient->reveal(),
            $apiKey,
            $blog
        );

        $isCommentAcceptable = $service->isCommentAcceptable(
            $comment,
            $userIp,
            $userAgent
        );

        self::assertFalse($isCommentAcceptable);
    }

    public function testIsCommentAcceptableReturnsTrueWhenResponseBodyEqualsTrue(): void
    {
        $comment = <<<TXT
Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna. 

Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute 
irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat 
cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
TXT;

        $apiKey = 'hello123';
        $blog = 'foo-bar-baz';

        $userIp = '123.456.789.012';
        $userAgent = 'Foo/9000';

        $httpClient = $this->prophesize(ClientInterface::class);

        $httpClient
            ->request(
                Argument::is('POST'),
                Argument::is(sprintf(
                    'https://%s.rest.akismet.com/1.1/comment-check',
                    $apiKey
                )),
                Argument::is([
                    'body' => http_build_query([
                        'blog' => $blog,
                        'comment_content' => $comment,
                        'user_agent' => $userAgent,
                        'user_ip' => $userIp,
                    ]),
                ])
            )
            ->shouldBeCalled()
            ->willReturn(new Psr7\Response(
                Http::OK,
                [],
                'true'
            ));

        $service = new SpamCheckService(
            $httpClient->reveal(),
            $apiKey,
            $blog
        );

        $isCommentAcceptable = $service->isCommentAcceptable(
            $comment,
            $userIp,
            $userAgent
        );

        self::assertTrue($isCommentAcceptable);
    }
}
