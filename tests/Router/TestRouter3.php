<?php

namespace Joindin\Api\Test\Router;

use BadMethodCallException;
use Joindin\Api\Request;
use Joindin\Api\Router\BaseRouter;
use Joindin\Api\Router\Route;

final class TestRouter3 extends BaseRouter
{
    /**
     * {@inheritdoc}
     */
    public function dispatch(Route $route, Request $request, \PDO $db): never
    {
        throw new BadMethodCallException('Method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getRoute(Request $request): Route
    {
        throw new BadMethodCallException('Method not implemented');
    }

    public function route(Request $request, \PDO $db): void
    {
    }
}
