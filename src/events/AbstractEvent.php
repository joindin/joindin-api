<?php

namespace Joindin\Events;

use Symfony\Component\EventDispatcher\Event;

abstract class AbstractEvent extends Event
{
    /**
     * @return string
     */
    public static function getEventName()
    {
        return str_replace('\\', '_', static::class);
    }

    public function getName()
    {
        return static::getEventName();
    }
}
