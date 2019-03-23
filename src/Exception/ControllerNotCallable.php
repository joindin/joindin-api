<?php

declare(strict_types=1);

namespace Joindin\Api\Exception;

use function htmlentities;
use RuntimeException;

class ControllerNotCallable extends RuntimeException
{
    public function __construct(string $controllerName)
    {
        return parent::__construct(
            sprintf('The requested controller "%s" ist not callable', htmlentities($controllerName)),
            500
        );
    }
}