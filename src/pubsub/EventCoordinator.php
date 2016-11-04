<?php

namespace Joindin\Pubsub;

use Symfony\Component\EventDispatcher\EventDispatcher;

class EventCoordinator
{
    protected $eventManager = null;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->eventManager = $dispatcher;
    }

    public function trigger(EventInterface $event)
    {
        $this->eventManager->dispatch($event->getName(), $event);
    }

    public function addListener(ListenerInterface $listener)
    {
        foreach ($listener->getCallbacks() as $eventname => $callback) {
            $this->eventManager->addListener($eventname, $callback);
        }
    }
}
