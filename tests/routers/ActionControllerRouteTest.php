<?php

/**
 * Class to test Route
 *
 * @covers Route
 */
class ActionControllerRouteTest extends PHPUnit_Framework_TestCase
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
     * @covers Route::__construct
     *
     * @param string $controller
     * @param array $params
     */
    public function testConstruct($controller, array $params)
    {
        $route = new ActionControllerRoute($controller, $params);
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
     * @covers Route::getController
     * @covers Route::setController
     * @covers Route::getAction
     * @covers Route::setAction
     * @covers Route::getParams
     * @covers Route::setParams
     *
     * @param string $controller
     * @param array $params
     */
    public function testGetSet($controller, array $params)
    {
        $route = new ActionControllerRoute('a', ['b']);

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
            'actioncontroller' => 'TestController5',
            'request' => $this->getRequest('v2.1')
        ], [ // #1
            'config' => ['config'],
            'actioncontroller' => 'TestController6',
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
     * @covers Route::dispatch
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

        $route = new ActionControllerRoute($controller);

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
        $request = $this->getMock('Request', ['getUrlElement'], [], '', false);

        $request->expects($this->any())
                ->method('getUrlElement')
                ->with(1)
                ->will($this->returnValue($urlElement));

        return $request;
    }
}

class TestController5
{
    public function __construct($config, Request $request, PDO $db)
    {
        //
    }

    public function __invoke()
    {
        return 'val';
    }
}

class TestController6
{
    public function __construct($config, Request $request, PDO $db)
    {
        //
    }
}
