<?php
// @codingStandardsIgnoreFile
include '../inc/Autoloader.php';
include '../inc/Request.php';
include '../inc/Header.php';
if (!function_exists('apache_request_headers')) {
    include '../inc/nginx-helper.php';
}

// Add exception handler
function handle_exception($e)
{
    // pull the correct format before we bail
    global $request, $config;
    $status_code = $e->getCode() ?: 400;
    $status_code = is_numeric($status_code) ? $status_code : 500;
    $request->getView()->setResponseCode($status_code);

    if ($status_code === 401) {
        $request->getView()->setHeader('WWW-Authenticate', 'Bearer realm="api.joind.in');
    }

    $message = $e->getMessage();
    if ($e instanceof PDOException && (!isset($config['mode']) || $config['mode'] !== "development")) {
        $message = "Database error";
    }
    $request->getView()->render(array($message));
}

set_exception_handler('handle_exception');

// config setup
define('BASEPATH', '.');
include '../config.php';
if ($config['mode'] == "development") {
    ini_set("html_errors", 0);
}

// database setup
include '../database.php';
$ji_db = new PDO(
    'mysql:host=' . $db['default']['hostname'] .
    ';dbname=' . $db['default']['database'],
    $db['default']['username'],
    $db['default']['password']
);
$ji_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Set the correct charset for this connection
$ji_db->query("SET NAMES 'utf8' COLLATE 'utf8_general_ci'");
$ji_db->query('SET CHARACTER SET utf8');


// collect URL and headers
$request = new Request($config, $_SERVER);

// identify our user if applicable
$headers = array_change_key_case(apache_request_headers(), CASE_LOWER);
if (isset($headers['authorization'])) {
    $request->identifyUser($ji_db, $headers['authorization']);
}

// Which content type to return? Parameter takes precedence over accept headers
// with final fall back to json 
$format_choices = array('application/json', 'text/html');
$header_format = $request->preferredContentTypeOutOf($format_choices);
$format = $request->getParameter('format', $header_format);

$routers = [
    "v2.1" => new VersionedRouter('2.1', $config, $rules),
    '' => new DefaultRouter($config),
];
$router = new ApiRouter($config, $routers, ['2']);

$route = $router->getRoute($request);
$return_data = $route->dispatch($request, $ji_db, $config);

if ($return_data && isset($request->user_id)) {
    $return_data['meta']['user_uri'] = $request->base . '/' . $request->version . '/users/' . $request->user_id;
}

// Handle output
// TODO sort out headers, caching, etc
$request->view->render($return_data);
exit;

/**
 *
 * @param Request $request
 * @param PDO $ji_db
 * @return array
 */
function routeV2($request, $ji_db, $config)
{
    $ratelimit = new \Joindin\Api\Middleware\RateLimit(new UserMapper($ji_db, $request));
    $request = $ratelimit($request);
    // Route: call the handle() method of the class with the first URL element
    if(isset($request->url_elements[2])) {
        $class = ucfirst($request->url_elements[2]) . 'Controller';
        if(class_exists($class)) {
            $handler = new $class($config);
            $return_data = $handler->handle($request, $ji_db); // the DB is set by the database config
        } else {
            throw new Exception('Unknown controller ' . $request->url_elements[2], 400);
        }
    } else {
        throw new Exception('Request not understood', 404);
    }
    
    return $return_data;
}


