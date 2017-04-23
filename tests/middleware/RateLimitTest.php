<?php
/**
 * Copyright (c) Andreas Heigl<andreas@heigl.org>
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @author    Andreas Heigl<andreas@heigl.org>
 * @copyright Andreas Heigl
 * @license   http://www.opensource.org/licenses/mit-license.php MIT-License
 * @since     13.12.2016
 * @link      http://github.com/heiglandreas/joindin-vm
 */

namespace JoindinTest\Api\Middleware;

use Joindin\Api\Middleware\RateLimit;

class RateLimitTest extends \PHPUnit_Framework_TestCase
{
    private $mapper;

    public function setUp()
    {
        $this->mapper = $this->getMockBuilder('UserMapper')->disableOriginalConstructor()->getMock();
    }

    public function testThatInstantiatingWorks()
    {

        $middleware = new RateLimit($this->mapper);

        $this->assertAttributeSame($this->mapper, 'userMapper', $middleware);
    }

    public function testThatGetIsNotRateLimited()
    {
        $request = $this->getMockBuilder('Request')->disableOriginalConstructor()->getMock();
        $request->method('getVerb')->willReturn('GET');

        $middleware = new RateLimit($this->mapper);

        $this->assertSame($request, $middleware($request));
    }

    public function testThatPostIsNotRateLmitedWhenUserIsMissing()
    {
        $request = $this->getMockBuilder('Request')->disableOriginalConstructor()->getMock();
        $request->method('getVerb')->willReturn('POST');
        $request->method('getUserId')->willReturn(null);

        $middleware = new RateLimit($this->mapper);

        $this->assertSame($request, $middleware($request));
    }

    public function testThatPostIsRateLimitedWhenRateIsNotExceeded()
    {
        $view = $this->getMockBuilder('ApiView')->disableOriginalConstructor()->getMock();
        $view->expects($this->exactly(3))
            ->method('setHeader')
            ->withConsecutive(
                [$this->equalTo('X-RateLimit-Limit'), $this->equalTo('-1')],
                [$this->equalTo('X-RateLimit-Remaining'), $this->equalTo('1')],
                [$this->equalTo('X-RateLimit-Reset'), $this->equalTo('100')]
            );

        $request = $this->getMockBuilder('Request')->disableOriginalConstructor()->getMock();
        $request->method('getVerb')->willReturn('POST');
        $request->method('getUserId')->willReturn(10);
        $request->method('getView')->willReturn($view);

        $this->mapper->method('getCurrentRateLimit')->with(10)->willReturn([
            'limit' => -1,
            'remaining' => 1,
            'reset' => 100,
        ]);
        $this->mapper->expects($this->once())->method('countdownRateLimit')->with(10);

        $middleware = new RateLimit($this->mapper);

        $this->assertSame($request, $middleware($request));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage API rate limit exceeded for foo
     * @expectedExceptionCode 403
     */
    public function testThatExceptionIsThrownWhenRateLmitIsExceeded()
    {
        $view = $this->getMockBuilder('ApiView')->disableOriginalConstructor()->getMock();
        $view->expects($this->exactly(3))
             ->method('setHeader')
             ->withConsecutive(
                 [$this->equalTo('X-RateLimit-Limit'), $this->equalTo('-1')],
                 [$this->equalTo('X-RateLimit-Remaining'), $this->equalTo('0')],
                 [$this->equalTo('X-RateLimit-Reset'), $this->equalTo('100')]
             );

        $request = $this->getMockBuilder('Request')->disableOriginalConstructor()->getMock();
        $request->method('getVerb')->willReturn('POST');
        $request->method('getUserId')->willReturn(10);
        $request->method('getView')->willReturn($view);

        $this->mapper->method('getCurrentRateLimit')->with(10)->willReturn([
            'limit' => -1,
            'remaining' => 0,
            'reset' => 100,
            'user' => 'foo',
        ]);
        $this->mapper->expects($this->exactly(0))->method('countdownRateLimit');

        $middleware = new RateLimit($this->mapper);

        $middleware($request);

    }

}
