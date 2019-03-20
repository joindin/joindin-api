<?php

namespace Joindin\Api\Test\Router;

use Exception;
use Joindin\Api\Request;
use Joindin\Api\Router\Route;
use PHPUnit\Framework\TestCase;

/**
 * Class to test Route
 *
 * @covers \Joindin\Api\Router\Route
 */
class RouteTest extends TestCase
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
     * @covers       \Joindin\Api\Router\Route::__construct
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
     * @covers       \Joindin\Api\Router\Route::getController
     * @covers       \Joindin\Api\Router\Route::setController
     * @covers       \Joindin\Api\Router\Route::getAction
     * @covers       \Joindin\Api\Router\Route::setAction
     * @covers       \Joindin\Api\Router\Route::getParams
     * @covers       \Joindin\Api\Router\Route::setParams
     *
     * @param string $controller
     * @param string $action
     * @param array  $params
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
                'config'              => array('config'),
                'controller'          => 'Joindin\Api\Test\Router\TestController3',
                'action'              => 'action',
                'Joindin\Api\Request' => $this->getRequest('v1')
            ),
            array( // #1
                'config'                => array('config'),
                'controller'            => 'Joindin\Api\Test\Router\TestController3',
                'action'                => 'action2',
                'Joindin\Api\Request'   => $this->getRequest('v1'),
                'expectedException'     => 'Exception',
                'expectedExceptionCode' => 500,
            ),
            array( // #2
                'config'                => array('config'),
                'controller'            => 'TestController4',
                'action'                => 'action2',
                'Joindin\Api\Request'   => $this->getRequest('v1'),
                'expectedException'     => 'Exception',
                'expectedExceptionCode' => 400,
                'controllerExists'      => false
            )
        );
    }

    /**
     * @dataProvider dispatchProvider
     *
     * @covers       \Joindin\Api\Router\Route::dispatch
     *
     * @param array   $config
     * @param string  $controller
     * @param string  $action
     * @param Request $request
     * @param string  $expectedException
     * @param integer $expectedExceptionCode
     *
     * @throws Exception
     */
    public function testDispatch(
        array $config,
        $controller,
        $action,
        Request $request,
        $expectedException = false,
        $expectedExceptionCode = false,
        $controllerExists = true
    ) {
        $db        = 'database';
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
                ->willReturn(new $controller);
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
     * Gets a Request for testing
     *
     * @param string $urlElement
     *
     * @return Request
     */
    private function getRequest($urlElement)
    {
        $request = $this->getMock('Joindin\Api\Request', array('getUrlElement'), array(), '', false);

        $request->expects($this->any())
                ->method('getUrlElement')
                ->with(1)
                ->will($this->returnValue($urlElement));

        return $request;
    }
}
