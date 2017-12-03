<?php

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
        return new Route('DefaultController', 'handle');
    }
}
