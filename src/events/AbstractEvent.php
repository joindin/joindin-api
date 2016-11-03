<?php

namespace Joindin\Events;

use Symfony\Component\EventDispatcher\Event;

abstract class AbstractEvent extends Event
{
    public function dispatch()
    {
        $this->getEventManager()->dispatch(
            $this->getEventName(),
            $this
        );
    }

    protected function getEventManager()
    {
        return EventManagerFactory::getEventManager();
    }

    /**
     * @return string
     */
    protected function getEventName()
    {
        return str_replace('\\', '_', strtolower(get_class));
    }
}
