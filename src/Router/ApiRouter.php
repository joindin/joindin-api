<?php

namespace Joindin\Api\Router;

use Exception;
use Joindin\Api\Request;

/**
 * The main API Router; acts as the version selector
 */
class ApiRouter extends BaseRouter
{

    /**
     * @var array The configuration for this Router
     */
    protected $config;

    /**
     * @var array A list of supported versions and Routers
     */
    private $routers;

    /**
     * @var array A list of versions once but no longer supported
     */
    private $oldVersions;

    /**
     * @var string The latest version of the API
     */
    private $latestVersion;

    /**
     * Constructs a new Router
     *
     * @param array $config The application configuration
     * @param array $routers
     * @param       $oldVersions
     */
    public function __construct(array $config, array $routers, array $oldVersions)
    {
        parent::__construct($config);
        $this->setRouters($routers);
        $this->oldVersions = $oldVersions;
    }

    /**
     * Sets the list of Router classes this ApiRouter uses
     *
     * @param array $routers A list of Routers indexed by version
     */
    public function setRouters(array $routers)
    {
        $k = array_keys($routers);
        rsort($k);
        $this->latestVersion = current($k);
        $this->routers       = $routers;
    }

    /**
     * Gets the list of Router classes this ApiRouter uses
     *
     * @return array
     */
    public function getRouters()
    {
        return $this->routers;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute(Request $request)
    {
        $version = $request->getUrlElement(1);
        if (!$version) {
            // empty version, set request to use the latest
            $request->version = $this->latestVersion;
        } else {
            $request->version = $version;
        }

        // now route on the original $version
        if (isset($this->routers[$version])) {
            $router = $this->routers[$version];

            return $router->getRoute($request);
        }

        if (in_array(str_replace('v', '', $request->version), $this->oldVersions)) {
            throw new Exception("This API version is no longer supported. Please use {$this->latestVersion}");
        }

        throw new Exception('API version must be specified', 404);
    }
}
