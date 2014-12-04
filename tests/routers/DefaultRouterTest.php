<?php

/**
 * A class to test DefaultRouter
 *
 * @covers DefaultRouter
 */
class DefaultRouterTest extends PHPUnit_Framework_TestCase
{

    /**
     * DataProvider for testRoute
     *
     * @return array
     */
    public function routeProvider()
    {
        return array(
            array( // #0
                'request' => $this->getRequest(array('', 'v1', 'test'))
            )
        );
    }

    /**
     * @dataProvider routeProvider
     *
     * @covers DefaultRouter::route
     *
     * @param Request $request
     */
    public function testRoute(Request $request)
    {
        $db = 'database';
        $value = 'val';
        $obj = new TestRouter2(array('xyz' => 'abc'));

        $this->assertEquals($value, $obj->route($request, $db));
    }

    /**
     * @covers DefaultRouter::getClass
     */
    public function testGetClass()
    {
        $obj = new DefaultRouter(array('xyz' => 'abc'));
        $this->assertEquals('DefaultController', $obj->getClass());
    }

    /**
     * Gets a Request for testing
     *
     * @param array $urlElements
     *
     * @return Request
     */
    private function getRequest(array $urlElements)
    {
        $request = $this->getMock('Request', array('getUrlElement'), array(), '', false);
        $request->url_elements = $urlElements;
        return $request;
    }
}

class TestRouter2 extends DefaultRouter
{
    public function getClass()
    {
        return 'TestController2';
    }
}

class TestController2 extends ApiController
{
    public function __construct(array $config) {
        if (!isset($config['xyz'])) {
            throw new Exception('xyz', 1001);
        }
    }

    public function handle(Request $req, $db) {
        if ($db == 'database') {
            return 'val';
        }
    }
}