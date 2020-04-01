<?php

namespace Joindin\Api\Test\Router;

use Exception;
use Joindin\Api\Request;
use Joindin\Api\Router\Route;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Teapot\StatusCode\Http;

/**
 * Class to test Route
 *
 * @covers \Joindin\Api\Router\Route
 */
final class RouteTest extends TestCase
{
    /**
     * DataProvider for testConstruct
     *
     * @return array
     */
    public function constructProvider()
    {
        return [
            ['controller', 'action', ['a' => 'b']]
        ];
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
        return [
            ['TestController', 'testAction', ['event_id' => 1]]
        ];
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
        $route = new Route('a', 'b', ['c']);

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
        return [
            [ // #0
                'config'              => ['config'],
                'controller'          => TestController3::class,
                'action'              => 'action',
                Request::class => $this->getRequest('v1')
            ],
            [ // #1
                'config'                => ['config'],
                'controller'            => TestController3::class,
                'action'                => 'action2',
                Request::class => $this->getRequest('v1'),
                'expectedException'     => 'Exception',
                'expectedExceptionCode' => Http::INTERNAL_SERVER_ERROR,
            ],
            [ // #2
                'config'                => ['config'],
                'controller'            => 'TestController4',
                'action'                => 'action2',
                Request::class => $this->getRequest('v1'),
                'expectedException'     => 'Exception',
                'expectedExceptionCode' => Http::BAD_REQUEST,
                'controllerExists'      => false
            ]
        ];
    }

    /**
     * @dataProvider dispatchProvider
     *
     * @covers       \Joindin\Api\Router\Route::dispatch
     *
     * @param array $config
     * @param string $controller
     * @param string $action
     * @param Request $request
     * @param bool $expectedException
     * @param bool $expectedExceptionCode
     * @param bool $controllerExists
     * @throws \ReflectionException
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
        $container = $this->getMockBuilder(ContainerInterface::class)
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
            $route->dispatch($request, $db, $container);
        } catch (Exception $ex) {
            if (!$expectedException) {
                throw $ex;
            }
            $this->assertInstanceOf($expectedException, $ex);

            if ($expectedExceptionCode !== false) {
                if ($expectedExceptionCode !== $ex->getCode()) {
                    var_dump(
                        $action,
                        $expectedException,
                        $expectedExceptionCode,
                        $ex->getCode(),
                        $ex->getMessage(),
                        $ex->getTrace()
                    );
                }
                $this->assertEquals($expectedExceptionCode, $ex->getCode());
            }
        }
    }

    /**
     * Gets a Request for testing
     *
     * @param string $urlElement
     * @return Request&MockObject
     * @throws \ReflectionException
     */
    private function getRequest($urlElement)
    {
        $request = $this->createMock(Request::class);

        $request->expects($this->any())
            ->method('getUrlElement')
            ->with($this->isType('integer'), true)
            ->willReturn($urlElement);

        return $request;
    }
}
