<?php

namespace Joindin\Api\Router;

use Joindin\Api\Request;
use Psr\Http\Message\ServerRequestInterface;

interface RequestModifierInterface
{
    public function __invoke(Request $request) : ServerRequestInterface;
}