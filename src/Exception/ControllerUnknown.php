<?php

declare(strict_types=1);

namespace Joindin\Api\Exception;

use function htmlentities;
use RuntimeException;

class ControllerUnknown extends RuntimeException
{
    public function __construct(string $controllerName)
    {
        return parent::__construct(
            sprintf('No such controller found: %s', htmlentities($controllerName)),
            400
        );
    }
}