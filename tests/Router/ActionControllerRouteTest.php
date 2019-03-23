<?php

declare(strict_types=1);

namespace Joindin\Api\Test\Router;

use Exception;
use Joindin\Api\Request;
use Joindin\Api\Router\ActionControllerRoute;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class to test Route
 *
 * @covers \Joindin\Api\Router\Route
 */
class ActionControllerRouteTest extends TestCase
{
    /**
     * DataProvider for testConstruct
     *
     * @return array
     */
    public function constructProvider()
    {
        return [
            ['controller', ['a' => 'b']],
        ];
    }

    /**
     * @dataProvider constructProvider
     * @covers \Joindin\Api\Router\Route::__construct
     *
     * @param string $controller
     * @param array $params
     */
    public function testConstruct($controller, array $params)
    {
        $route = new ActionControllerRoute($controller, $params, new TestRequestModifier());
        $this->assertEquals($controller, $route->getController());
        $this->assertEquals('__invoke', $route->getAction());
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
            ['TestController', ['event_id' => 1]],
        ];
    }

    /**
     * @dataProvider getSetProvider
     * @covers \Joindin\Api\Router\Route::getController
     * @covers \Joindin\Api\Router\Route::setController
     * @covers \Joindin\Api\Router\Route::getAction
     * @covers \Joindin\Api\Router\Route::setAction
     * @covers \Joindin\Api\Router\Route::getParams
     * @covers \Joindin\Api\Router\Route::setParams
     *
     * @param string $controller
     * @param array $params
     */
    public function testGetSet($controller, array $params)
    {
        $route = new ActionControllerRoute('a', ['b'], new TestRequestModifier());

        $route->setController($controller);
        $this->assertEquals($controller, $route->getController());

        $route->setAction('Foo');
        $this->assertEquals('__invoke', $route->getAction());

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
        return [[ // #0
            'config' => ['config'],
            'actioncontroller' => TestController5::class,
            'request' => $this->getRequest('v2.1')
        ], [ // #1
            'config' => ['config'],
            'actioncontroller' => TestController6::class,
            'request' => $this->getRequest('v2.1'),
            'expectedException' => 'RuntimeException',
            'expectedExceptionCode' => 500
        ], [ // #2
            'config' => ['config'],
            'actioncontroller' => 'TestController7',
            'request' => $this->getRequest('v2.1'),
            'expectedException' => 'RuntimeException',
            'expectedExceptionCode' => 400
        ]];
    }

    /**
     * @dataProvider dispatchProvider
     *
     * @covers \Joindin\Api\Router\Route::dispatch
     *
     * @param array $config
     * @param string $controller
     * @param Request $request
     * @param string $expectedException
     * @param integer $expectedExceptionCode
     *
     * @throws Exception
     */
    public function testDispatch(array $config, $controller, Request $request, $expectedException = false, $expectedExceptionCode = false)
    {
        $db = new PDO('sqlite:memory:');

        $route = new ActionControllerRoute($controller, [], new TestRequestModifier());

        try {
            $this->assertInstanceof(ResponseInterface::class, $route->dispatch($request, $db, $config));
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
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request->expects($this->any())
                ->method('getUrlElement')
                ->with(1)
                ->will($this->returnValue($urlElement));

        return $request;
    }
}
