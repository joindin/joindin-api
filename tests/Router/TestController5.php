<?php

declare(strict_types=1);

namespace Joindin\Api\Test\Router;

use Joindin\Api\Request;
use Nyholm\Psr7\Response;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Dummy controller implementation
 */
class TestController5 implements RequestHandlerInterface
{
    public function __construct($config, PDO $db)
    {
        //
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new Response();
    }
}