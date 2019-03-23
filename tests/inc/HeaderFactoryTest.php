<?php
/**
 * Copyright Andrea Heigl <andreas@heigl.org>
 *
 * Licenses under the MIT-license. For details see the included file LICENSE.md
 */

namespace JoindinTest\inc;

use Joindin\Api\Inc\HeaderFactory;
use PHPUnit\Framework\TestCase;

class HeaderFactoryTest extends TestCase
{
    /** @dataProvider invokationProvider */
    public function testInvokation($headers, $expect)
    {
        $factory = new HeaderFactory();

        self::assertEquals($expect, $factory($headers));
    }

    public function invokationProvider() : array
    {
        return [
            [[
                'HTTP_ACCEPT' => 'accept',
                'HTTP_CONTENT_TYPE' => 'content-type',
                'TEST' => 'foo'
            ],[
                'Accept' => 'accept',
                'Content-Type' => 'content-type',
            ]]
        ];
    }
}
