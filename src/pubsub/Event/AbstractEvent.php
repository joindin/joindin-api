<?php

namespace Joindin\Pubsub\Event;

use Joindin\Pubsub\EventInterface;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractEvent extends Event implements EventInterface
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
