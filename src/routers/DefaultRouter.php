<?php

/**
 * The default API Router
 */
class DefaultRouter extends Router
{

    /**
     * {@inheritdoc}
     */
    public function route(Request $request, $db)
    {
        $class = $this->getClass();
        $controller = new $class($this->config);
        return $controller->handle($request, $db);
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