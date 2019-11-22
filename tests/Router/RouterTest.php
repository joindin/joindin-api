<?php

namespace Joindin\Api\Test\Router;

use PHPUnit\Framework\TestCase;

/**
 * A class to test DefaultRouter
 *
 * @covers \Joindin\Api\Router\BaseRouter
 */
final class RouterTest extends TestCase
{
    /**
     * DataProvider for testConstruct
     *
     * @return array
     */
    public function constructProvider()
    {
        return [
            [ // #0
                'config' => ['xyz']
            ]
        ];
    }

    /**
     * @dataProvider constructProvider
     *
     * @covers       \Joindin\Api\Router\BaseRouter::__construct
     * @covers       \Joindin\Api\Router\BaseRouter::getConfig
     *
     * @param array $config
     */
    public function testConstruct(array $config)
    {
        $obj = new TestRouter3($config);
        $this->assertEquals($config, $obj->getConfig());
    }
}
