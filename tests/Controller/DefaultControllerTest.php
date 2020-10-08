<?php

declare(strict_types=1);

namespace Joindin\Api\Test\Controller;

use Joindin\Api\Controller\DefaultController;
use Joindin\Api\Request;
use PDO;
use PHPUnit\Framework\TestCase;

class DefaultControllerTest extends TestCase
{
    public function testHandle(): void
    {
        $request = $this->createMock(Request::class);
        $request->base = 'base';
        $request->version = '1.0';

        $controller = new DefaultController();

        $expected = [
            'events' => 'base/1.0/events',
            'hot-events' => 'base/1.0/events?filter=hot',
            'upcoming-events' => 'base/1.0/events?filter=upcoming',
            'past-events' => 'base/1.0/events?filter=past',
            'open-cfps' => 'base/1.0/events?filter=cfp',
            'docs' => 'http://joindin.github.io/joindin-api/',
        ];

        $this->assertSame($expected, $controller->handle($request, $this->createMock(PDO::class)));
    }
}
