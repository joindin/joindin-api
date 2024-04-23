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
     */
    private string $controller;

    /**
     * The method name this Route will target
     */
    private string $action;

    /**
     * Parameters derived from the URL this Route was created from
     */
    private array $params;

    /**
     * Constructs a new Route
     *
     * @param string $controller The name of the controller this Route is for
     * @param string $action     The name of the action this Route is for
     * @param array  $params     Parameters as determined from the URL
     */
    public function __construct(string $controller, string $action, array $params = [])
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
    public function getController(): string
    {
        return $this->controller;
    }

    /**
     * Sets the name of the controller this Route is for
     *
     * @param string $controller
     */
    public function setController($controller): void
    {
        $this->controller = $controller;
    }

    /**
     * Gets the action this Route is for
     *
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Sets the action this Route is for
     *
     * @param string $action
     */
    public function setAction($action): void
    {
        $this->action = $action;
    }

    /**
     * Gets the parameters for this Route
     *
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Sets the parameters for this Route
     *
     * @param array $params
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Dispatches the Request to the specified Route
     *
     * @param Request            $request   The Request to process
     * @param PDO|string         $db        The Database object
     * @param ContainerInterface $container The application configuration
     *
     * @return mixed
     */
    public function dispatch(Request $request, PDO|string $db, ContainerInterface $container): mixed
    {
        $className = $this->getController();
        $method    = $this->getAction();

        if (!$container->has($className)) {
            throw new RuntimeException('Unknown controller ' . $request->getUrlElement(2), Http::BAD_REQUEST);
        }

        $controller = $container->get($className);

        if (!is_object($controller)) {
            throw new RuntimeException('Retrieved controller is not an object', Http::INTERNAL_SERVER_ERROR);
        }

        if (!method_exists($controller, $method)) {
            throw new RuntimeException('Action not found', Http::INTERNAL_SERVER_ERROR);
        }

        if (function_exists('newrelic_name_transaction')) {
            $classPathComponents = explode('\\', $className);
            $controllerWithoutNamespace = end($classPathComponents);
            newrelic_name_transaction($controllerWithoutNamespace . '.' . $method);
        }

        return $controller->$method($request, $db);
    }
}
