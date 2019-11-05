<?php

namespace Joindin\Api\Test\Router;

use Joindin\Api\Request;

final class TestController3
{
    public function action(Request $request, $db)
    {
        if ($db === 'database') {
            return 'val';
        }
    }
}
