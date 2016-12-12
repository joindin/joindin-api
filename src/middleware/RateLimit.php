<?php

namespace Joindin\Api\Middleware;

class RateLimit
{
    private $userMapper;

    private $verbs = [
        'POST',
        'PUT',
        'DELETE',
        'UPDATE',
    ];

    public function __construct(\UserMapper $userMapper)
    {
        $this->userMapper = $userMapper;
    }

    public function __invoke(\Request $request)
    {
        if (! in_array($request->getVerb(), $this->verbs)) {
            return $request;
        }

        if (! $request->getUserId()) {
            return $request;
        }

        $userMapper = $this->getUserMapper();

        if (! $userMapper->hasValidRateLimit($request->getUserId())) {
            throw new Exception('RateLimit Exceeded');
        }

        $userMapper->countdownRateLimit($request->getUserId());

        return $request;
    }


    private function getUserMapper()
    {
        return $this->userMapper;
    }
}
