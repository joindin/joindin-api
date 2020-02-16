<?php

/**
 * Create new consumer key
 *
 * @return array
 */
function new_consumer_key()
{
    $fp = fopen('/dev/urandom', 'rb');
    $entropy = fread($fp, 32);
    fclose($fp);
    // in case /dev/urandom is reusing entropy from its pool,
    // let's add a bit more entropy
    $entropy .= uniqid(mt_rand(), true);
    $hash = sha1($entropy);  // sha1 gives us a 40-byte hash
    // The first 30 bytes should be plenty for the consumer_key
    // We use the last 10 for the shared secret
    return [substr($hash, 0, 30), substr($hash, 30, 10)];
}
