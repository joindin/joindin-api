<?php

namespace Joindin\Api\Router;

use PDO;
use Psr\Container\ContainerInterface;
use Joindin\Api\Request;
use RuntimeException;
use Teapot\StatusCode\Http;

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
     * @param string $action     The name of the action this Route is for
     * @param array  $params     Parameters as determined from the URL
     */
    public function __construct($controller, $action, array $params = [])
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
     * @param Request            $request   The Request to process
     * @param PDO                $db        The Database object
     * @param ContainerInterface $container The application configuration
     *
     * @return mixed
     */
    public function dispatch(Request $request, $db, ContainerInterface $container)
    {
        $className = $this->getController();
        $method    = $this->getAction();

        if (!$container->has($className)) {
            throw new RuntimeException('Unknown controller ' . $request->getUrlElement(2), Http::BAD_REQUEST);
        }

        if (!method_exists($controller = $container->get($className), $method)) {
            throw new RuntimeException('Action not found', Http::INTERNAL_SERVER_ERROR);
        }

        if (function_exists('newrelic_name_transaction')) {
            $controllerWithoutNamespace = @end(explode('\\', $className));
            newrelic_name_transaction($controllerWithoutNamespace . '.' . $method);
        }

        return $controller->$method($request, $db);
    }
}
