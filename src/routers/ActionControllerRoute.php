<?php

/**
 * Represents a Controller and action to dispatch a Request to.
 */
class ActionControllerRoute extends Route
{
    /**
     * Constructs a new Route
     *
     * @param string $controller The name of the controller this Route is for
     * @param string $action The name of the action this Route is for
     * @param array $params Parameters as determined from the URL
     */
    public function __construct($controller, array $params = [])
    {
        $this->setController($controller);
        $this->setParams($params);
    }

    /**
     * Gets the action this Route is for
     *
     * @return string
     */
    public function getAction()
    {
        return '__invoke';
    }

    /**
     * Sets the action this Route is for
     *
     * @param string $action
     */
    public function setAction($action)
    {
        // Do nothing on purpose
    }

    /**
     * Dispatches the Request to the specified Route
     *
     * @param Request $request The Request to process
     * @param PDO $db The Database object
     * @param mixed $config The application configuration
     *
     * @return mixed
     */
    public function dispatch(Request $request, $db, $config)
    {
        $className = $this->getController();
        if (! class_exists($className)) {
            throw new RuntimeException('Unknown controller ' . $request->url_elements[2], 400);
        }

        $controller = new $className($config, $request, $db);
        if (! is_callable($controller)) {
            throw new RuntimeException('Controller not callable', 500);
        }
        
        return $controller();
    }
}
