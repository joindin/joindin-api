<?php

declare(strict_types=1);

namespace Joindin\Api\Inc;

use function array_map;
use function strpos;
use function strtolower;
use function ucfirst;

class HeaderFactory
{
    public function __invoke(array $server) : array
    {
        $return = [];
        foreach($server as $key => $value) {
            if (strpos($key, 'HTTP_') !== 0) {
                continue;
            }

            $key = str_replace('HTTP_', '', $key);
            $key = implode('-', array_map(function($string) {
                return ucfirst(strtolower($string));
            }, explode('_', $key)));

            $return[$key] = $value;
        }
        return $return;
    }
}