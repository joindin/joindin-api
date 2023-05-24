<?php

namespace Joindin\Api\View;

use Teapot\StatusCode\Http;

class ApiView
{
    protected $headers;

    /** @var int */
    protected $responseCode;

    /** @var bool */
    protected $noRender;

    public function __construct(array $headers = [], $responseCode = Http::OK, $noRender = false)
    {
        $this->headers      = $headers;
        $this->responseCode = $responseCode;
        $this->noRender     = $noRender;
    }

    /**
     * @param mixed $content
     *
     * @return mixed
     */
    protected function addCount($content)
    {
        if (is_array($content)) {
            foreach ($content as $name => $item) {
                // count what's in the non-meta element
                if ($name == "meta") {
                    continue;
                }

                if (is_array($item)) {
                    $content['meta']['count'] = count($item);
                }
            }
        }

        return $content;
    }

    public function setHeader(string $header, string $value): void
    {
        $this->headers[$header] = $value;
    }

    public function setResponseCode(int $code): void
    {
        $this->responseCode = $code;
    }

    public function setNoRender(bool $noRender): void
    {
        $this->noRender = $noRender;
    }

    /**
     * @param array|string $content
     *
     * @return bool
     */
    public function render(array|string $content): bool
    {
        ob_start();
        $body = '';

        if ($content && $this->noRender === false) {
            $body = $this->buildOutput($content);
        }

        if (Http::OK == $this->responseCode) {
            $this->responseCode = (int) http_response_code();
        }

        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value, true);
        }
        http_response_code($this->responseCode);

        echo $body;
        ob_end_flush();

        return true;
    }

    public function buildOutput(array|string $content): string|false|null
    {
        return null;
    }
}
