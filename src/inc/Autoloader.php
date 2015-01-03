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

	$filename = false;

    if (preg_match('/[a-zA-Z]+Controller$/', $classname)) {
        $filename = __DIR__ . '/../controllers/' . $classname . '.php';
    } elseif (preg_match('/[a-zA-Z]+Mapper$/', $classname)) {
        $filename = __DIR__ . '/../models/' . $classname . '.php';
    } elseif (preg_match('/[a-zA-Z]+Model$/', $classname)) {
        $filename = __DIR__ . '/../models/' . $classname . '.php';
    } elseif (preg_match('/[a-zA-Z]+View$/', $classname)) {
        $filename = __DIR__ . '/../views/' . $classname . '.php';
    } elseif (preg_match('/[a-zA-Z]+Service$/', $classname)) {
        $filename = __DIR__ . '/../services/' . $classname . '.php';
    } elseif (preg_match('/Router?$/', $classname)) {
        $filename = __DIR__ . '/../routers/' . $classname . '.php';
    }

	if (file_exists($filename)) {
		include $filename;
		return true;
	}
}
