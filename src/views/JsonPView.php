<?php

class JsonPView extends JsonView
{
    /** @var string */
    protected $_callback;

    public function __construct($callback)
    {
        parent::__construct();
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
