<?php

class ApiView
{
    /** @var array */
    protected $headers;

    /** @var int */
    protected $responseCode;

    /** @var bool */
    protected $noRender;

    public function __construct(array $headers = [], $responseCode = 200, $noRender = false)
    {
        $this->headers = $headers;
        $this->responseCode = $responseCode;
        $this->noRender = $noRender;
    }

    protected function addCount($content)
    {
        if (is_array($content)) {
            foreach ($content as $name => $item) {
                // count what's in the non-meta element
                if ($name == "meta") {
                    continue;
                } elseif (is_array($item)) {
                    $content['meta']['count'] = count($item);
                }
            }
        }

        return $content;
    }

    public function setHeader($header, $value)
    {
        $this->headers[$header] = $value;
    }

    public function setResponseCode($code)
    {
        $this->responseCode = $code;
    }

    public function setNoRender($noRender)
    {
        $this->noRender = $noRender;
    }

    public function render($content)
    {
        $body = '';
        if ($content && $this->noRender === false) {
            $body = $this->buildOutput($content);
        }
        if (200 == $this->responseCode) {
            $this->responseCode = http_response_code();
        }
        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value, true);
        }
        http_response_code($this->responseCode);

        echo $body;

        return true;
    }

    public function buildOutput($content)
    {
        return null;
    }
}
