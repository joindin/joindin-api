<?php

namespace Joindin\Api\Exception;

use Throwable;

final class RateLimitExceededException extends \Exception
{
    /** @var int */
    private $rate_limit_refresh;

    /** @var int */
    private $rate_limit_limit;

    private function __construct(string $message, int $code, Throwable $previous = null)
    {
        /*
         * The only reason for the constructor overwrite is to change the visibility
         * and prevent creating an instance without the required parameters
         */
        parent::__construct($message, $code, $previous);
    }

    public static function withLimitAndRefresh(int $rate_limit_limit, int $rate_limit_refresh): self
    {
        $message = sprintf('Rate limit exceeded. Try again in %d seconds', $rate_limit_refresh);
        $code = 429;

        $exception = new self($message, $code);
        $exception->rate_limit_limit = $rate_limit_limit;
        $exception->rate_limit_refresh = $rate_limit_refresh;

        return $exception;
    }

    public function getRateLimitLimit(): int
    {
        return $this->rate_limit_limit;
    }

    public function getRateLimitRefresh(): int
    {
        return $this->rate_limit_refresh;
    }
}
