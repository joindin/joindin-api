<?php

namespace Joindin\Api\Test\Router;

use Joindin\Api\Request;

final class TestController3
{
    public function action(Request $request, string $db): ?string
    {
        if ($db === 'database') {
            return 'val';
        }

        return null;
    }
}
