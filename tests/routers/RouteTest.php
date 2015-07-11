<?php

/**
 * Class to test Route
 *
 * @covers Route
 */
class RouteTest extends PHPUnit_Framework_TestCase
{
    /**
     * DataProvider for testConstruct
     * 
     * @return array 
     */
    public function constructProvider()
    {
        return array(
            array('controller', 'action', array('a' => 'b'))
        );
    }

    /**
     * @dataProvider constructProvider
     * @covers Route::__construct
     *
     * @param string $controller
     * @param string $action
     * @param array $params
     */
    public function testConstruct($controller, $action, array $params)
    {
        $route = new Route($controller, $action, $params);
        $this->assertEquals($controller, $route->getController());
        $this->assertEquals($action, $route->getAction());
        $this->assertEquals($params, $route->getParams());
    }

    /**
     * DataProvider for testGetSet
     * 
     * @return array 
     */
    public function getSetProvider()
    {
        return array(
            array('TestController', 'testAction', array('event_id' => 1))
        );
    }

    /**
     * @dataProvider getSetProvider
     * @covers Route::getController
     * @covers Route::setController
     * @covers Route::getAction
     * @covers Route::setAction
     * @covers Route::getParams
     * @covers Route::setParams
     *
     * @param string $controller
     * @param string $action
     * @param array $params
     */
    public function testGetSet($controller, $action, array $params)
    {
        $route = new Route('a', 'b', array('c'));

        $route->setController($controller);
        $this->assertEquals($controller, $route->getController());

        $route->setAction($action);
        $this->assertEquals($action, $route->getAction());

        $route->setParams($params);
        $this->assertEquals($params, $route->getParams());
    }

    /**
     * DataProvider for testDispatch
     *
     * @return array
     */
    public function dispatchProvider()
    {
        return array(
            array( // #0
                'config' => array('config'),
                'controller' => 'TestController3',
                'action' => 'action',
                'request' => $this->getRequest('v1')
            ),
            array( // #1
                'config' => array('config'),
                'controller' => 'TestController3',
                'action' => 'action2',
                'request' => $this->getRequest('v1'),
                'expectedException' => 'Exception',
                'expectedExceptionCode' => 500
            ),
            array( // #2
                'config' => array('config'),
                'controller' => 'TestController4',
                'action' => 'action2',
                'request' => $this->getRequest('v1'),
                'expectedException' => 'Exception',
                'expectedExceptionCode' => 400
            )
        );
    }

    /**
     * @dataProvider dispatchProvider
     *
     * @covers Route::dispatch
     *
     * @param array $config
     * @param string $controller
     * @param string $action
     * @param Request $request
     * @param string $expectedException
     * @param integer $expectedExceptionCode
     * 
     * @throws Exception
     */
    public function testDispatch(array $config, $controller, $action, Request $request, $expectedException = false, $expectedExceptionCode = false)
    {
        $db = 'database';

        $route = new Route($controller, $action);

        try {
            $this->assertEquals('val', $route->dispatch($request, $db, $config));
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

class TestController3
{
    public function action(Request $request, $db)
    {
        if ($db == 'database') {
            return 'val';
        }
    }
}
