<?php

/**
 * Represents a Controller and action to dispatch a Request to.
 */
class Route
{
    /**
     * The name of the Controller this Route will target
     *
     * @var string
     */
    private $controller;

    /**
     * The method name this Route will target
     *
     * @var string
     */
    private $action;

    /**
     * Parameters derived from the URL this Route was created from
     *
     * @var array
     */
    private $params;

    /**
     * Constructs a new Route
     *
     * @param string $controller The name of the controller this Route is for
     * @param string $action The name of the action this Route is for
     * @param array $params Parameters as determined from the URL
     */
    public function __construct($controller, $action, $params = array())
    {
        $this->setController($controller);
        $this->setAction($action);
        $this->setParams($params);
    }

    /**
     * Gets the name of the controller this Route is for
     *
     * @return string
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Sets the name of the controller this Route is for
     *
     * @param string $controller
     */
    public function setController($controller)
    {
        $this->controller = $controller;
    }

    /**
     * Gets the action this Route is for
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Sets the action this Route is for
     *
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * Gets the parameters for this Route
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Sets the parameters for this Route
     *
     * @param array $params
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * Dispatches the Request to the specified Route
     *
     * @param Request $request The Request to process
     * @param mixed $db The Database object
     * @param mixed $config The application configuration
     * @param EventCoordinator $ec
     *
     * @return mixed
     */
    public function dispatch(Request $request, $db, $config, $ec = null)
    {
        $className = $this->getController();
        $method    = $this->getAction();
        if (class_exists($className)) {
            $controller = new $className($config);
            if (method_exists($controller, $method)) {
                return $controller->$method($request, $db, $ec);
            }
            throw new RuntimeException('Action not found', 500);
        }
        throw new RuntimeException('Unknown controller ' . $request->url_elements[2], 400);
    }
}
