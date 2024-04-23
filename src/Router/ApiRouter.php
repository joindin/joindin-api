<?php

namespace Joindin\Api\Router;

use Exception;
use Joindin\Api\Request;
use Teapot\StatusCode\Http;

/**
 * The main API Router; acts as the version selector
 */
class ApiRouter extends BaseRouter
{
    protected array $config;
    private array $routers;

    /**
     * @var string The latest version of the API
     */
    private $latestVersion;

    /**
     * Constructs a new Router
     *
     * @param array $config The application configuration
     * @param array $routers
     * @param array $oldVersions
     */
    public function __construct(array $config, array $routers, private array $oldVersions)
    {
        parent::__construct($config);
        $this->setRouters($routers);
    }

    /**
     * Sets the list of Router classes this ApiRouter uses
     *
     * @param array<int,string> $routers A list of Routers indexed by version
     */
    public function setRouters(array $routers): void
    {
        $k = array_keys($routers);
        rsort($k);
        $this->latestVersion = (string) current($k);
        $this->routers       = $routers;
    }

    /**
     * Gets the list of Router classes this ApiRouter uses
     *
     * @return array
     */
    public function getRouters(): array
    {
        return $this->routers;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute(Request $request): Route
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

        throw new Exception('API version must be specified', Http::NOT_FOUND);
    }
}
