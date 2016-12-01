<?php

namespace Joindin\Pubsub\Event;

class PendingEventCreated extends AbstractEvent
{

    protected $event;

    public function __construct($event)
    {
        $this->event = $event;
    }

    public function getEvent()
    {
        return $this->event;
    }
}
