<?php

/**
 * A Router to route v2.1 Routes
 */
class V2_1Router extends Router
{

    /**
     * {@inheritdoc}
     */
    public function getRoute(Request $request)
    {
        $conf = [
            [
                'path' => '/events/?',
                'controller' => 'EventsController',
                'action' => 'getAction',
                'methods' => [Request::HTTP_GET]
            ],
            [
                'path' => '/events/?',
                'controller' => 'EventsController',
                'action' => 'postAction',
                'methods' => [Request::HTTP_POST]
            ],
            [
                'path' => '/events/?',
                'controller' => 'EventsController',
                'action' => 'putAction',
                'methods' => [Request::HTTP_PUT]
            ],
            [
                'path' => '/events/?',
                'controller' => 'EventsController',
                'action' => 'deleteAction',
                'methods' => [Request::HTTP_DELETE]
            ]
        ];

        foreach ($conf as $rule) {
            if (isset($rule['methods']) && !in_array($request->getVerb(), $rule['methods'])) {
                continue;
            }
            if (preg_match('%^/v2.1' . $rule['path'] . '%', $request->getPathInfo(), $matches)) {
                return new Route($rule['controller'], $rule['action']);
            }
        }

        return $this->getLegacyRoute($request);
    }

    /**
     * This is the old behaviour; it provides legacy support for
     *
     * @see Router::getRoute
     */
    public function getLegacyRoute(Request $request)
    {
        if (isset($request->url_elements[2])) {
            $class = ucfirst($request->url_elements[2]) . 'Controller';
            return new Route($class, 'handle');
        }
        throw new Exception('Request not understood', 404);
    }

}