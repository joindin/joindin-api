<?php

namespace Joindin\Pubsub;

interface EventInterface
{
    /**
     * @return string
     */
    public static function getEventName();

    public function getName();
}
