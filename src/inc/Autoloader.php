<?php
/**
 * Autoloader
 *
 * PHP version 5
 *
 * @category Inc
 * @package  APIv2_Tests
 * @author   Rob Allen <rob@akrabat.com>
 * @license  BSD see doc/LICENSE
 * @link     http://github.com/joindin/joind.in
 */

include __DIR__ . '/../../vendor/autoload.php';

$relativePaths = array(
    __DIR__ . '/../inc',
    __DIR__ . '/../controllers',
    __DIR__ . '/../models',
    __DIR__ . '/../views',
    __DIR__ . '/../services',
    __DIR__ . '/../routers',
);

$includePath = implode(PATH_SEPARATOR, $relativePaths);
set_include_path(get_include_path() . PATH_SEPARATOR . $includePath);

spl_autoload_register('apiv2Autoload');

/**
 * Autoloader
 * 
 * @param string $classname name of class to load
 * 
 * @return boolean
 */
function apiv2Autoload($classname)
{
    if (false !== strpos($classname, '.')) {
        // this was a filename, don't bother
        exit;
    }

    $path = str_replace(array('_', '\\'), DIRECTORY_SEPARATOR, $classname) . '.php';

    if ($filePath = stream_resolve_include_path($path)) {
        include_once($filePath);
        return true;
    }

    // Fallback for legacy support (Event_commentsController etc)
    if ($filePath = stream_resolve_include_path($classname . '.php')) {
        include_once($filePath);
        return true;
    }

    return false;
}