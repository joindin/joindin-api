<?php

class JsonPView extends JsonView
{
    /** @var callable */
    protected $_callback;

    public function __construct(callable $callback)
    {
        $this->_callback = $callback;
        parent::__construct();
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
