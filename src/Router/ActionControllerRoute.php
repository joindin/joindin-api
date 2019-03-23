<?php

declare(strict_types = 1);

namespace Joindin\Api\Router;

use Joindin\Api\Exception\ControllerNotCallable;
use Joindin\Api\Exception\ControllerUnknown;
use Joindin\Api\Inc\QueueRequestHandler;
use Joindin\Api\Request;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

class ActionControllerRoute extends Route
{
    private $requestModifier;
    /**
     * Constructs a new Route
     *
     * @param string $controller The name of the controller this Route is for
     * @param string $action The name of the action this Route is for
     * @param array $params Parameters as determined from the URL
     */
    public function __construct($controller, array $params = [], RequestModifierInterface $requestModifier)
    {
        $this->setController($controller);
        $this->setParams($params);
        $this->requestModifier = $requestModifier;
    }

    /**
     * Gets the action this Route is for
     *
     * @return string
     */
    public function getAction()
    {
        return '__invoke';
    }

    /**
     * Sets the action this Route is for
     *
     * @param string $action
     */
    public function setAction($action)
    {
        // Do nothing on purpose
    }

    /**
     * Dispatches the Request to the specified Route
     *
     * @param ServerRequestInterface $request The Request to process
     * @param PDO $db The Database object
     * @param mixed $config The application configuration
     *
     * @return mixed
     */
    public function dispatch(Request $request, $db, $config) : ResponseInterface
    {
        $request = ($this->requestModifier)($request);
        $className = $this->getController();
        if (! class_exists($className)) {
            throw new ControllerUnknown($className);
        }

        $controller = new $className($config, $db);
        if (! $controller instanceof RequestHandlerInterface) {
            throw new ControllerNotCallable($className);
        }

        $queue = new QueueRequestHandler($controller);

        // Here we can later add adding middleware

        $response = $queue->handle($request);

        return $response;
    }
}
