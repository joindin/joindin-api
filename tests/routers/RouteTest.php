<?php

use PHPUnit\Framework\TestCase;

/**
 * Class to test Route.
 *
 * @covers Route
 */
class RouteTest extends TestCase
{
    /**
     * DataProvider for testConstruct.
     *
     * @return array
     */
    public function constructProvider()
    {
        return [
            ['controller', 'action', ['a' => 'b']],
        ];
    }

    /**
     * @dataProvider constructProvider
     * @covers Route::__construct
     *
     * @param string $controller
     * @param string $action
     * @param array  $params
     */
    public function testConstruct($controller, $action, array $params)
    {
        $route = new Route($controller, $action, $params);
        $this->assertEquals($controller, $route->getController());
        $this->assertEquals($action, $route->getAction());
        $this->assertEquals($params, $route->getParams());
    }

    /**
     * DataProvider for testGetSet.
     *
     * @return array
     */
    public function getSetProvider()
    {
        return [
            ['TestController', 'testAction', ['event_id' => 1]],
        ];
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
     * @param array  $params
     */
    public function testGetSet($controller, $action, array $params)
    {
        $route = new Route('a', 'b', ['c']);

        $route->setController($controller);
        $this->assertEquals($controller, $route->getController());

        $route->setAction($action);
        $this->assertEquals($action, $route->getAction());

        $route->setParams($params);
        $this->assertEquals($params, $route->getParams());
    }

    /**
     * DataProvider for testDispatch.
     *
     * @return array
     */
    public function dispatchProvider()
    {
        return [
            [ // #0
                'config'     => ['config'],
                'controller' => 'TestController3',
                'action'     => 'action',
                'request'    => $this->getRequest('v1'),
            ],
            [ // #1
                'config'                => ['config'],
                'controller'            => 'TestController3',
                'action'                => 'action2',
                'request'               => $this->getRequest('v1'),
                'expectedException'     => 'Exception',
                'expectedExceptionCode' => 500,
            ],
            [ // #2
                'config'                => ['config'],
                'controller'            => 'TestController4',
                'action'                => 'action2',
                'request'               => $this->getRequest('v1'),
                'expectedException'     => 'Exception',
                'expectedExceptionCode' => 400,
                'controllerExists'      => false,
            ],
        ];
    }

    /**
     * @dataProvider dispatchProvider
     *
     * @covers Route::dispatch
     *
     * @param array   $config
     * @param string  $controller
     * @param string  $action
     * @param Request $request
     * @param string  $expectedException
     * @param int     $expectedExceptionCode
     *
     * @throws Exception
     */
    public function testDispatch(array $config, $controller, $action, Request $request, $expectedException = false, $expectedExceptionCode = false, $controllerExists = true)
    {
        $db = 'database';
        $container = $this->getMockBuilder(\Psr\Container\ContainerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container
            ->expects($this->atLeastOnce())
            ->method('has')
            ->willReturn($controllerExists);

        if ($controllerExists) {
            $container
                ->expects($this->atLeastOnce())
                ->method('get')
                ->willReturn(new $controller());
        }

        $route = new Route($controller, $action);

        try {
            $this->assertEquals('val', $route->dispatch($request, $db, $container));
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
     * Gets a Request for testing.
     *
     * @param string $urlElement
     *
     * @return Request
     */
    private function getRequest($urlElement)
    {
        $request = $this->getMock('Request', ['getUrlElement'], [], '', false);

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
