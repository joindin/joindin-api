<?php

namespace Joindin\Api\View;

class JsonPView extends JsonView
{
    public function __construct(protected string $callback)
    {
        parent::__construct();
    }

    public function render($content): bool
    {
        $this->setHeader('Content-Type', 'text/javascript; charset=utf8');

        return parent::render($content);
    }

    public function buildOutput(mixed $content): string
    {
        return $this->callback . '(' . parent::buildOutput($content) . ');';
    }
}
