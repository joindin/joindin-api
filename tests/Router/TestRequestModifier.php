<?php

declare(strict_types=1);

namespace Joindin\Api\Test\Router;

use Joindin\Api\Request;
use Joindin\Api\Router\RequestModifierInterface;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class TestRequestModifier implements RequestModifierInterface
{
    public function __invoke(Request $request) : ServerRequestInterface
    {

        return new ServerRequest(
            'GET',
            'https://example.com'
        );
    }
}