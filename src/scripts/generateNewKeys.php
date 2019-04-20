<?php

/**
 * Generate New Keys.
 *
 * @category Joined.In
 *
 * @author   Chris Cornutt <ccornutt@phpdeveloper.org>
 * @license  http://github.com/joindin/joind.in/blob/master/doc/LICENSE JoindIn
 */
require_once __DIR__.'/newConsumerKeyFunc.php';

$keys = new_consumer_key();

echo "INSERT INTO oauth_consumers SET consumer_key = '".
     $keys[0]."', consumer_secret = '".$keys[1]."'";
