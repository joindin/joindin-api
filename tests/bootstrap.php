<?php
// Load and register autoloader
require_once __DIR__ . '/../src/inc/Autoloader.php';

//We are unit testing
define('UNIT_TEST', 1);

/**
 * Class to allow for mocking PDO to send to the OAuthModel
 */
class mockPDO extends \PDO
{
    /**
     * Constructor that does nothing but helps us test with fake database
     * adapters
     */
    public function __construct()
    {
        // We need to do this crap because PDO has final on the __sleep and
        // __wakeup methods. PDO requires a parameter in the constructor but we don't
        // want to create a real DB adapter. If you tell getMock to not call the
        // original constructor, it fakes stuff out by unserializing a fake
        // serialized string. This way, we've got a "PDO" object but we don't need
        // PHPUnit to fake it by unserializing a made-up string. We've neutered
        // the constructor in mockPDO.
    }
}

class_alias(mockPDO::class, 'JoindinTest\Inc\mockPDO');
