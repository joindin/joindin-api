<?php

declare(strict_types=1);

namespace Joindin\Api\Inc;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class QueueRequestHandler implements RequestHandlerInterface
{
    private $middleware = [];

    private $fallbackHandler;

    public function __construct(RequestHandlerInterface $fallback)
    {
        $this->fallbackHandler = $fallback;
    }

    public function add(MiddlewareInterface $middleware)
    {
        $this->middleware[] = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (count($this->middleware) === 0) {
            return $this->fallbackHandler->handle($request);
        }

        $middleware = array_shift($this->middleware);

        return $middleware->process($request, $this);
    }
}