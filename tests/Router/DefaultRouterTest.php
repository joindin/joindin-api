<?php

namespace Joindin\Api\Test\Router;

use Joindin\Api\Controller\DefaultController;
use Joindin\Api\Request;
use Joindin\Api\Router\DefaultRouter;
use PHPUnit\Framework\TestCase;

/**
 * A class to test DefaultRouter
 *
 * @covers \Joindin\Api\Router\DefaultRouter
 */
final class DefaultRouterTest extends TestCase
{

    /**
     * DataProvider for testGetRoute
     *
     * @return array
     */
    public function getRouteProvider()
    {
        return [
            [ // #0
                'url' => '/v1/test'
            ]
        ];
    }

    /**
     * @dataProvider getRouteProvider
     *
     * @covers       \Joindin\Api\Router\DefaultRouter::getRoute
     *
     * @param string $url
     */
    public function testGetRoute($url)
    {
        $request = new Request([], ['REQUEST_URI' => $url]);
        $router  = new DefaultRouter([]);
        $route   = $router->getRoute($request);
        $this->assertEquals(DefaultController::class, $route->getController());
        $this->assertEquals('handle', $route->getAction());
    }
}
