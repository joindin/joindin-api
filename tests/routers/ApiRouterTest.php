<?php

require_once(__DIR__ . '/../../src/inc/Request.php');

/**
 * A class to test ApiRouter
 *
 * @covers ApiRouter
 * @covers Request
 */
class ApiRouterTest extends PHPUnit_Framework_TestCase
{


    /**
     * DataProvider for testGetSetRouters
     *
     * @return array
     */
    public function getSetRoutersProvider()
    {
        return array(
            array(
                'routers' => array(
                    2 => 'B',
                    1 => 'A',
                    3 => 'C'
                ),
                'expected' => array(
                    1 => 'A',
                    2 => 'B',
                    3 => 'C'
                )
            )
        );
    }

    /**
     * @dataProvider getSetRoutersProvider
     *
     * @covers ApiRouter::getRouters
     * @covers ApiRouter::setRouters
     *
     * @param array $routers A list of Routers
     * @param array $expected The expected result of getRouters
     */
    public function testGetSetRouters(array $routers, array $expected)
    {
        $obj = new ApiRouter(array(), array(), array());
        $obj->setRouters($routers);
        $this->assertEquals($expected, $obj->getRouters());
    }



    /**
     * DataProvider for testGetRoute
     *
     * @return array
     * @todo work in progress @damko
     */
    public function getRouteProvider()
    {
        return array(
            array(
                'routers' => array(
                    "v2.1" => new VersionedRouter('2.1', array(), array()),
                ),
                'url' => '/v1/test',
            )
        );
    }

    /**
     *
     * @covers ApiRouter::getRoute
     *
     * @dataProvider getRouteProvider
     *
     * @param string $url
     * @todo work in progress @damko
     */
     public function testGetRoute(array $routers, $url)
     {
        $request = new Request([], ['REQUEST_URI' => $url]);

        //No oldVersions set
        $obj = new ApiRouter(array(), $routers, array());
        $this->setExpectedException('Exception', 'API version must be specified');
        $obj->getRoute($request);

        //oldVersions set
        $obj = new ApiRouter(array(), $routers, array('1','2'));
        $this->setExpectedException('Exception', 'API version must be specified');
        $obj->getRoute($request);
     }

}