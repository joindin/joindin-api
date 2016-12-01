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
include('../config.php');
if ($config['mode'] == "development") {
    ini_set("html_errors", 0);
}

// database setup
include('../database.php');
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

// @TODO This feels just a tad... shonky.
$rules = json_decode(file_get_contents('../config/routes/2.1.json'), true);

$routers = [
    "v2.1" => new VersionedRouter('2.1', $config, $rules),
    '' => new DefaultRouter($config),
];
$router = new ApiRouter($config, $routers, ['2']);

// Set up the event-listeners
$ec = new \Joindin\Pubsub\EventCoordinator(new \Symfony\Component\EventDispatcher\EventDispatcher());
$ec->addListener(new \Joindin\Pubsub\Listener\EmailListener(new EventbasedEmailService($config), new UserMapper($ji_db, $request)));

$route = $router->getRoute($request);
$return_data = $route->dispatch($request, $ji_db, $config, $ec);

if ($return_data && isset($request->user_id)) {
    $return_data['meta']['user_uri'] = $request->base . '/' . $request->version . '/users/' . $request->user_id;
}

// Handle output
// TODO sort out headers, caching, etc
$request->getView()->render($return_data);
