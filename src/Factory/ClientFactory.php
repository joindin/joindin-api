<?php


namespace Joindin\Api\Factory;


use GuzzleHttp\Client;

class ClientFactory
{
    public function createClient(array $config = []) {
        return new Client($config);
    }
}
