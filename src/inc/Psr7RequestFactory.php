<?php

declare(strict_types=1);

namespace Joindin\Api\Inc;

use Nyholm\Psr7\Stream;
use Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request as Psr7Request;

/**
 * Copyright Andrea Heigl <andreas@heigl.org>
 *
 * Licenses under the MIT-license. For details see the included file LICENSE.md
 */
class Psr7RequestFactory
{
    private $psr17Factory;

    private $headerFactory;

    public function __construct(Psr17Factory $factory, HeaderFactory $headerFactory)
    {
        $this->psr17Factory = $factory;
        $this->headerFactory = $headerFactory;
    }

    public function __invoke(array $server) : Psr7Request
    {
        $headers  = ($this->headerFactory)($server);
        $protocol = str_replace('HTTP/', '', $server['SERVER_PROTOCOL']);
        $verb     = $server['REQUEST_METHOD'];
        $scheme   = 'http';
        if (isset($server['HTTPS'])) {
            $scheme = 'https';
        }

        $uri = $this->$this->psr17Factory->createUri(
            $scheme . '://' . $server['HTTP_HOST'] . $server['REQUEST_URI']
        );

        $stream = Stream::create(fopen('php://input', 'r'));

        return new Psr7Request($verb, $uri, $headers, $stream, $protocol);
    }

    public static function create($server)
    {
        $factory = new self(new Psr17Factory(), new HeaderFactory());
        return $factory($server);

    }
}