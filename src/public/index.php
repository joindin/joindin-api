<?php
include '../inc/Autoloader.php';
include '../inc/Request.php';
include '../inc/Timezone.php';
include '../inc/Header.php';
if (!function_exists('apache_request_headers')) {
    include '../inc/nginx-helper.php';
}

// Add exception handler
function handle_exception($e) {
    // pull the correct format before we bail
    global $request;
    $status_code = $e->getCode() ?: 400;
    header("Status: " . $status_code, false, $status_code);
	$request->getView()->render(array($e->getMessage()));
}

set_exception_handler('handle_exception');

// config setup
define('BASEPATH', '.');
include('../config.php');
if($config['mode'] == "development") {
    ini_set("html_errors", 0);
}

// database setup
include('../database.php');
$ji_db = new PDO('mysql:host=' . $db['default']['hostname'] . 
    ';dbname=' . $db['default']['database'],
    $db['default']['username'],
    $db['default']['password']);

// Set the correct charset for this connection
$ji_db->query("SET NAMES 'utf8' COLLATE 'utf8_general_ci'");
$ji_db->query('SET CHARACTER SET utf8');


// collect URL and headers
$request = new Request($config, $_SERVER);

// identify our user if applicable
$headers = apache_request_headers();
if(isset($headers['Authorization'])) {
    $request->identifyUser($ji_db, $headers['Authorization']);
} else if(isset($headers['authorization'])) {
    $request->identifyUser($ji_db, $headers['authorization']);
}

// @TODO This feels just a tad... shonky.
$rules = json_decode(file_get_contents('../config/routes/2.1.json'), true);

$routers = [
    "v2.1" => new VersionedRouter('2.1', $config, $rules),
    '' => new DefaultRouter($config),
];
$router = new ApiRouter($config, $routers, ['2']);

$route = $router->getRoute($request);
$return_data = $route->dispatch($request, $ji_db, $config);

if(isset($request->user_id)) {
    $return_data['meta']['user_uri'] = $request->base . '/' . $request->version . '/users/' . $request->user_id;
}

// Handle output
// TODO sort out headers, caching, etc
$request->getView()->render($return_data);
