<?php

namespace Joindin\Api\Test\Router;

use Exception;
use Joindin\Api\Request;
use Joindin\Api\Router\VersionedRouter;
use PHPUnit\Framework\TestCase;
use Teapot\StatusCode\Http;

/**
 * A class to test VersionedRouter
 *
 * @covers \Joindin\Api\Router\VersionedRouter
 */
final class VersionedRouterTest extends TestCase
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
                'version'            => '2.1',
                'rules'              => [
                    [
                        'path'       => '/events',
                        'controller' => 'EventController',
                        'action'     => 'getAction'
                    ]
                ],
                'url'                => '/v2.1/events',
                'method'             => Request::HTTP_GET,
                'expectedController' => 'EventController',
                'expectedAction'     => 'getAction'
            ],
            [ // #1
                'version'            => '2.1',
                'rules'              => [
                    [
                        'path'       => '/aevents',
                        'controller' => 'AEventController',
                        'action'     => 'getSAction'
                    ],
                    [
                        'path'       => '/events',
                        'controller' => 'EventController',
                        'action'     => 'getAction'
                    ]
                ],
                'url'                => '/v2.1/events',
                'method'             => Request::HTTP_GET,
                'expectedController' => 'EventController',
                'expectedAction'     => 'getAction'
            ],
            [ // #2
                'version'            => '2.1',
                'rules'              => [
                    [
                        'path'       => '/events',
                        'controller' => 'EventController',
                        'action'     => 'getAction',
                        'verbs'      => [Request::HTTP_POST]
                    ],
                    [
                        'path'       => '/events',
                        'controller' => 'EventController2',
                        'action'     => 'getAction2',
                        'verbs'      => [Request::HTTP_GET, Request::HTTP_PUT]
                    ]
                ],
                'url'                => '/v2.1/events',
                'method'             => Request::HTTP_GET,
                'expectedController' => 'EventController2',
                'expectedAction'     => 'getAction2'
            ],
            [ // #3
                'version'            => '2.1',
                'rules'              => [
                    [
                        'path'       => '/events/(?P<event_id>\d+)$',
                        'controller' => 'EventController',
                        'action'     => 'getAction'
                    ],
                ],
                'url'                => '/v2.1/events/10',
                'method'             => Request::HTTP_GET,
                'expectedController' => 'EventController',
                'expectedAction'     => 'getAction',
                'routeParams'        => ['event_id' => 10]
            ],
            [ // #4
                'version'               => '2.1',
                'rules'                 => [
                    [
                        'path'       => '/aevents',
                        'controller' => 'AEventController',
                        'action'     => 'getSAction'
                    ],
                    [
                        'path'       => '/events',
                        'controller' => 'EventController',
                        'action'     => 'getAction',
                        'verbs'      => [Request::HTTP_GET]
                    ]
                ],
                'url'                   => '/v2.1/events',
                'method'                => Request::HTTP_POST,
                'expectedController'    => 'N/A',
                'expectedAction'        => 'N/A',
                'routeParams'           => [],
                'expectedExceptionCode' => Http::METHOD_NOT_ALLOWED
            ],
            [ // #5
                'version'               => '2.1',
                'rules'                 => [
                    [
                        'path'       => '/aevents',
                        'controller' => 'AEventController',
                        'action'     => 'getSAction'
                    ],
                    [
                        'path'       => '/events',
                        'controller' => 'EventController',
                        'action'     => 'getAction',
                        'verbs'      => [Request::HTTP_GET]
                    ]
                ],
                'url'                   => '/v2.2/events',
                'method'                => Request::HTTP_GET,
                'expectedController'    => 'N/A',
                'expectedAction'        => 'N/A',
                'routeParams'           => [],
                'expectedExceptionCode' => Http::NOT_FOUND
            ]
        ];
    }

    /**
     * @dataProvider getRouteProvider
     * @covers       \Joindin\Api\Router\VersionedRouter::getRoute
     *
     * @param float         $version
     * @param array         $rules
     * @param string        $url
     * @param string        $method
     * @param string        $expectedController
     * @param string        $expectedAction
     * @param array         $routeParams
     * @param integer|false $expectedExceptionCode
     * @throws Exception
     */
    public function testGetRoute(
        $version,
        array $rules,
        $url,
        $method,
        $expectedController,
        $expectedAction,
        array $routeParams = [],
        $expectedExceptionCode = false
    ) {
        $request = new Request([], ['REQUEST_URI' => $url, 'REQUEST_METHOD' => $method]);
        $router  = new VersionedRouter($version, [], $rules);

        try {
            $route = $router->getRoute($request);
        } catch (Exception $ex) {
            if (!$expectedExceptionCode) {
                throw $ex;
            }
            $this->assertEquals($expectedExceptionCode, $ex->getCode());

            return;
        }
        $this->assertEquals($expectedController, $route->getController());
        $this->assertEquals($expectedAction, $route->getAction());
        $this->assertEquals($routeParams, $route->getParams());
    }
}
