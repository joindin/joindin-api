<?php

namespace Joindin\Api\Router;

use Joindin\Api\Controller\DefaultController;
use Joindin\Api\Request;

/**
 * The default API Router
 */
class DefaultRouter extends BaseRouter
{
    /**
     * {@inheritdoc}
     */
    public function getRoute(Request $request): Route
    {
        return new Route(DefaultController::class, 'handle');
    }
}
