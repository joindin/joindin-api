<?php

declare(strict_types=1);

namespace Joindin\Api\Router;

use Joindin\Api\Request;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class JoindinRequestModifier implements RequestModifierInterface
{
    public function __invoke(Request $request): ServerRequestInterface
    {
        return new ServerRequest(
            $request->getVerb(),
            $request->getScheme() . $request->getHost() . $request->getPathInfo()
        );
    }
}