<?php

/**
 * The base Router class; contains configuration
 */
abstract class BaseRouter
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
     * @param Request $request The Request to route
     *
     * @return Route
     */
    abstract public function getRoute(Request $request);
}
