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
     * Constructs a new Route
     *
     * @param string $controller    The name of the controller this Route is for
     * @param string $action        The name of the action this Route is for
     */
    public function __construct($controller, $action)
    {
        $this->setController($controller);
        $this->setAction($action);
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

}