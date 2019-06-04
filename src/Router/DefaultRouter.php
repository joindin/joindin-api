<?php

namespace Joindin\Api\Router;

use Joindin\Api\Request;

/**
 * The default API Router
 */
class DefaultRouter extends BaseRouter
{

    /**
     * {@inheritdoc}
     */
    public function getRoute(Request $request)
    {
        return new Route('Joindin\Api\Controller\DefaultController', 'handle');
    }
}
