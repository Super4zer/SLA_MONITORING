<?php

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

// Load routes
require_once __DIR__ . '/../routes/api.php';

// Dispatch request
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$router->dispatch($method, $uri);
