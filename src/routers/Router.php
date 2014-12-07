<?php

/**
 * The base Router class; contains configuration
 */
abstract class Router
{
    /**
     * @var array The configuration for this Router
     */
    protected $config;

    /**
     * Constructs a new Router
     *
     * @param array $config The application configuration
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Gets this Router's config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Gets the Route appropriate to the passed Request
     * 
     * @param Request $request  The Request to route
     * 
     * @return Route
     */
    abstract public function getRoute(Request $request);

    /**
     * Routes the passed Request
     *
     * @param Request $request
     * @param mixed $db
     *
     * @return mixed
     */
    public function route(Request $request, $db)
    {
        $route = $this->getRoute($request);
        return $this->dispatch($route, $request, $db);
    }

    /**
     * Dispatches the Request to the specified Route
     *
     * @TODO  This functionality probably belongs either in
     *        the Route class or in a Dispatcher class.
     *
     * @param Route $route      The Route to dispatch
     * @param Request $request  The Request to process
     * @param mixed $db         The Database object
     */
    private function dispatch(Route $route, Request $request, $db)
    {
        $className = $route->getController();
        $method = $route->getAction();
        if (class_exists($className)) {
            $controller = new $className($this->config);
            if (method_exists($controller, $method)) {
                return $controller->$method($request, $db);
            }
            throw new RuntimeException('Action not found', 500);
        }
        throw new RuntimeException('Unknown controller ' . $request->url_elements[2], 400);
    }
}