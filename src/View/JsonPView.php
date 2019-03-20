<?php

namespace Joindin\Api\View;

class JsonPView extends JsonView
{
    /** @var string */
    protected $callback;

    public function __construct($callback)
    {
        parent::__construct();
        $this->callback = $callback;
    }

    public function render($content)
    {
        $this->setHeader('Content-Type', 'text/javascript; charset=utf8');

        return parent::render($content);
    }

    public function buildOutput($content)
    {
        return $this->callback . '(' . parent::buildOutput($content) . ');';
    }
}
