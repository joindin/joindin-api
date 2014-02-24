<?php

/**
 * Base Email Class
 *
 * This class provides a base for different email implementations
 *
 * @author Kim Rowan
 */
abstract class EmailBaseService
{

    abstract public function parseEmail();

    protected function dispatchEmail($messageBody)
    {
        //send email here
    }


}