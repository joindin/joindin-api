<?php

/**
 * A class to test ApiRouter
 *
 * @author Christopher Hoult <chris@choult.com>
 * @covers ApiRouter
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
     * DataProvider for testRoute
     *
     * @return array
     */
    public function routeProvider()
    {
        return array(
            array( // #0
                'config' => array('config'),
                'routers' => array('1' => 'TestRouter1', '2.1' => 'InvalidRouter'),
                'oldVersions' => array(),
                'request' => $this->getRequest('v1')
            ),
            array( // #1
                'config' => array('config'),
                'routers' => array('1' => 'InvalidRouter', '2.1' => 'TestRouter1'),
                'oldVersions' => array(),
                'request' => $this->getRequest('v2.1')
            ),
            array( // #2
                'config' => array('config'),
                'routers' => array('2' => 'InvalidRouterX', '2.1' => 'InvalidRouter'),
                'oldVersions' => array('1'),
                'request' => $this->getRequest('v1'),
                'expectedException' => 'Exception',
                'expectedExceptionCode' => 0
            ),
            array( // #3
                'config' => array('config'),
                'routers' => array('2' => 'InvalidRouterY', '2.1' => 'InvalidRouter'),
                'oldVersions' => array('1'),
                'request' => $this->getRequest('v3'),
                'expectedException' => 'Exception',
                'expectedExceptionCode' => 404
            )
        );
    }

    /**
     * @dataProvider routeProvider
     *
     * @covers ApiRouter::__construct
     * @covers ApiRouter::route
     *
     * @param array $config
     * @param array $routers
     * @param array $oldVersions
     * @param Request $request
     * @param string|false $expectedException
     * @param integer|false $expectedExceptionCode
     */
    public function testRoute(array $config, array $routers, array $oldVersions, Request $request, $expectedException = false, $expectedExceptionCode = false)
    {
        $db = 'database';
        $value = 'val';
        $obj = new ApiRouter($config, $routers, $oldVersions);

        try {
            $this->assertEquals($value, $obj->route($request, $db));
        } catch (Exception $ex) {
            if (!$expectedException) {
                throw $ex;
            }
            $this->assertInstanceOf($expectedException, $ex);
            if ($expectedExceptionCode !== false) {
                $this->assertEquals($expectedExceptionCode, $ex->getCode());
            }
        }
    }

    /**
     * Gets a Request for testing
     *
     * @param string $urlElement
     *
     * @return Request
     */
    private function getRequest($urlElement)
    {
        $request = $this->getMock('Request', array('getUrlElement'), array(), '', false);

        $request->expects($this->any())
                ->method('getUrlElement')
                ->with(1)
                ->will($this->returnValue($urlElement));

        return $request;
    }
}

class TestRouter1 extends Router
{
    public function route(Request $req, $db) {
        if ($db == 'database') {
            return 'val';
        }
    }
}