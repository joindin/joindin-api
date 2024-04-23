<?php

namespace Joindin\Api\Router;

use Joindin\Api\Request;

/**
 * The base Router class; contains configuration
 */
abstract class BaseRouter
{
    /**
     * Constructs a new Router
     *
     * @param array $config The application configuration
     */
    public function __construct(protected array $config)
    {
    }

    /**
     * Gets this Router's config
     *
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Gets the Route appropriate to the passed Request
     *
     * @param Request $request The Request to route
     *
     * @return Route
     */
    abstract public function getRoute(Request $request): Route;
}
