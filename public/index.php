<?php
// @codingStandardsIgnoreFile
use Joindin\Api\ContainerFactory;
use Joindin\Api\Request;
use Joindin\Api\Router\ApiRouter;
use Joindin\Api\Router\DefaultRouter;
use Joindin\Api\Router\VersionedRouter;
use Teapot\StatusCode\Http;

include __DIR__ . '/../vendor/autoload.php';
if (!function_exists('apache_request_headers')) {
    require __DIR__ . '/../inc/nginx-helper.php';
}
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}
// Add exception handler
function handle_exception(\Throwable $e)
{
    // pull the correct format before we bail
    global $request, $config;
    $status_code = $e->getCode() ?: Http::BAD_REQUEST;
    $status_code = is_numeric($status_code) ? $status_code : 500;
    $request->getView()->setResponseCode($status_code);

    if ($status_code === Http::UNAUTHORIZED) {
        $request->getView()->setHeader('WWW-Authenticate', 'Bearer realm="api.joind.in');
    }

    error_log(get_class($e) . ': ' . $e->getMessage() . " -- " . $e->getTraceAsString());

    $message = $e->getMessage();
    if ($e instanceof PDOException && (!isset($config['mode']) || $config['mode'] !== "development")) {
        $message = "Database error";
    }
    $request->getView()->render([$message]);
}

set_exception_handler('handle_exception');

// config setup
define('BASEPATH', '.');
$config = [];
require __DIR__. '/../src/config.php';

$container = ContainerFactory::build($config);

if ($config['mode'] == "development") {
    ini_set("html_errors", 0);
}

// database setup
$db = [];
require __DIR__ . '/../src/database.php';
$ji_db = new PDO(
    'mysql:host=' . $db['default']['hostname'] .
    ';dbname=' . $db['default']['database'] . ';charset=utf8mb4',
    $db['default']['username'],
    $db['default']['password']
);
$ji_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// collect URL and headers
$request = new Request($config, $_SERVER);

// identify our user if applicable
$headers = array_change_key_case(apache_request_headers(), CASE_LOWER);
if (isset($headers['authorization'])) {
    $request->identifyUser($headers['authorization'], $ji_db);
}

$rules = require __DIR__ .'/../src/config/routes/2.1.php';

$routers = [
    "v2.1" => new VersionedRouter('2.1', $config, $rules),
    '' => new DefaultRouter($config),
];
$router = new ApiRouter($config, $routers, ['2']);

$route = $router->getRoute($request);
$return_data = $route->dispatch($request, $ji_db, $container);
if (is_array($return_data) && isset($request->user_id)) {
    $return_data['meta']['user_uri'] = $request->base . '/' . $request->version . '/users/' . $request->user_id;
}
// Handle output
// TODO sort out headers, caching, etc
$request->getView()->render($return_data);
