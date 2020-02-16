<?php

namespace Joindin\Api\Test\Router;

use Joindin\Api\Router\ApiRouter;
use PHPUnit\Framework\TestCase;

/**
 * A class to test ApiRouter
 *
 * @covers \Joindin\Api\Router\ApiRouter
 */
final class ApiRouterTest extends TestCase
{
    /**
     * DataProvider for testGetSetRouters
     *
     * @return array
     */
    public function getSetRoutersProvider()
    {
        return [
            [
                'routers' => [
                    2 => 'B',
                    1 => 'A',
                    3 => 'C'
                ],
                'expected' => [
                    1 => 'A',
                    2 => 'B',
                    3 => 'C'
                ]
            ]
        ];
    }

    /**
     * @dataProvider getSetRoutersProvider
     *
     * @covers       \Joindin\Api\Router\ApiRouter::getRouters
     * @covers       \Joindin\Api\Router\ApiRouter::setRouters
     *
     * @param array $routers  A list of Routers
     * @param array $expected The expected result of getRouters
     */
    public function testGetSetRouters(array $routers, array $expected)
    {
        $obj = new ApiRouter([], [], []);
        $obj->setRouters($routers);
        $this->assertEquals($expected, $obj->getRouters());
    }
}
