<?php

/**
 * The default API Router
 */
class DefaultRouter extends Router
{

    /**
     * {@inheritdoc}
     */
    public function getRoute(Request $request)
    {
        return new Route('DefaultController', 'handle');
    }
}