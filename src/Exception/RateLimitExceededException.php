<?php

namespace Joindin\Api\Exception;

use Throwable;

final class RateLimitExceededException extends \Exception
{
    private $rate_limit_refresh;
    private $rate_limit_limit;

    private function __construct($message, $code, Throwable $previous = null)
    {
        /*
         * The only reason for the constructor overwrite is to change the visibility
         * and prevent creating an instance without the required parameters
         */
        parent::__construct($message, $code, $previous);
    }

    public static function withLimitAndRefresh(int $rate_limit_limit, int $rate_limit_refresh)
    {
        $message = sprintf('Rate limit exceeded. Try again in %d seconds', $rate_limit_refresh);
        $code = 429;

        $exception = new self($message, $code);
        $exception->rate_limit_limit = $rate_limit_limit;
        $exception->rate_limit_refresh = $rate_limit_refresh;

        return $exception;
    }

    /**
     * @return int
     */
    public function getRateLimitLimit()
    {
        return $this->rate_limit_limit;
    }

    /**
     * @return int
     */
    public function getRateLimitRefresh()
    {
        return $this->rate_limit_refresh;
    }
}
