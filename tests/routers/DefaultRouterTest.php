<?php

require_once(__DIR__ . '/../../src/inc/Request.php');

/**
 * A class to test DefaultRouter
 *
 * @covers DefaultRouter
 */
class DefaultRouterTest extends PHPUnit_Framework_TestCase
{
    /**
     * DataProvider for testGetRoute
     *
     * @return array
     */
    public function getRouteProvider()
    {
        return array(
            array( // #0
                'url' => '/v1/test'
            )
        );
    }

    /**
     * @dataProvider getRouteProvider
     *
     * @covers DefaultRouter::getRoute
     *
     * @param string $url
     */
    public function testGetRoute($url)
    {
        $request = new Request([], ['REQUEST_URI' => $url]);
        $router = new DefaultRouter([]);
        $route = $router->getRoute($request);
        $this->assertEquals('DefaultController', $route->getController());
        $this->assertEquals('handle', $route->getAction());
    }
}
