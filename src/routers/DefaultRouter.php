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
        return new Route($this->getClass(), 'handle');
    }

    /**
     * Wrapper for testability; provides the Controller class name
     *
     * @return string
     */
    public function getClass()
    {
        return 'DefaultController';
    }
}