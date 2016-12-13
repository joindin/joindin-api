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
        $rates = $userMapper->getCurrentRateLimit($request->getUserId());

        $request->getView()->setHeader('X-RateLimit-Limit', $rates['limit']);
        $request->getView()->setHeader('X-RateLimit-Remaining', $rates['remaining']);
        $request->getView()->setHeader('X-RateLimit-Reset', $rates['reset']);

        if (1 > $rates['remaining']) {
            throw new \Exception(sprintf(
                'API rate limit exceeded for %1$s',
                $rates['user']
            ), 403);
        }

        $userMapper->countdownRateLimit($request->getUserId());

        return $request;
    }


    private function getUserMapper()
    {
        return $this->userMapper;
    }
}
