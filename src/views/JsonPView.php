<?php

class JsonPView extends JsonView
{
    public function __construct($callback)
    {
        $this->_callback = $callback;
    }

    public function render($content)
    {
        $this->setHeader('Content-Type', 'text/javascript; charset=utf8');

        return parent::render($content);
    }

    public function buildOutput($content)
    {
        return $this->_callback . '(' . parent::buildOutput($content) . ');';
    }
}
