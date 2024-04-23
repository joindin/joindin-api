<?php

/**
 * Create new consumer key
 *
 * @return string[] First entry is consumer_key, second is shared secret
 */
function new_consumer_key(): array
{
    return [bin2hex(random_bytes(15)), bin2hex(random_bytes(5))];
}
