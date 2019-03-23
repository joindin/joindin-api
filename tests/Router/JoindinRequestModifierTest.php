<?php
/**
 * Copyright Andrea Heigl <andreas@heigl.org>
 *
 * Licenses under the MIT-license. For details see the included file LICENSE.md
 */

namespace Joindin\Api\Test\Router;

use Joindin\Api\Request;
use Joindin\Api\Router\JoindinRequestModifier;
use PHPUnit\Framework\TestCase;

class JoindinRequestModifierTest extends TestCase
{
    /**
     * @dataProvider provideData
     */
    public function testConversionFromJoindinRequestToServerRequestWorks(Request $request, $results)
    {
        $converter = new JoindinRequestModifier();

        $serverRequest = $converter($request);

        self::assertEquals($results['verb'], $serverRequest->getMethod());
        self::assertEquals($results['uri'], $serverRequest->getUri());
        self::assertEquals($results['headers'], $serverRequest->getHeaders());
    }

    public function provideData()
    {
        return [
            [new Request([], [
                'REQUEST_METHOD' => 'GET',
                'REQUEST_URI' => 'http://example.com/',
                'HTTP_HOST' => 'example.com',
            ]), [
                'verb' => 'GET',
                'uri'  => 'http://example.com/',
                'headers' => [
                    'Host' => ['example.com'],
                ],
            ]],
        ];
    }
}
