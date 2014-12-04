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
     * Routes the passed Request
     *
     * @param Request $request
     * @param mixed $db
     *
     * @return mixed
     */
    abstract public function route(Request $request, $db);
}