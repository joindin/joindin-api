<?php

class JsonPView extends JsonView
{
    /** @var string */
    protected $callback;

    public function __construct($callback)
    {
        $this->callback = $callback;
        parent::__construct();
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
