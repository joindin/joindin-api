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
}