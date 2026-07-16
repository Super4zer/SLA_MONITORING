<?php

// Handle static files for PHP built-in server
if (php_sapi_name() === 'cli-server') {
    $path = realpath(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
    if ($path && is_file($path) && strpos($path, __DIR__) === 0) {
        return false;
    }
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Env;
use App\Routing\Router;

// Load environment variables
try {
    Env::load(__DIR__ . '/../.env');
} catch (\Exception $e) {
    // If .env is missing, we log it and continue if variables are provided in system env.
    error_log($e->getMessage());
}

// Set default timezone
date_default_timezone_set(Env::get('TIMEZONE', 'Asia/Jakarta'));

// Initialize Router
$router = new Router();

// Log requests for debugging
file_put_contents(__DIR__ . '/../logs/request.log', date('[Y-m-d H:i:s] ') . $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'] . PHP_EOL, FILE_APPEND);

// Load routes
require_once __DIR__ . '/../routes/api.php';

// Dispatch request
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Fix for some server configurations where REQUEST_URI might include query string or subfolder
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}

// If the app is running in a subfolder, we should strip the subfolder path.
// For now, we assume it's at the root of the domain.

$router->dispatch($method, $uri);
